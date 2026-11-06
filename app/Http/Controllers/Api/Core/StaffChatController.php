<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\StaffChatConversation;
use App\Models\Core\StaffChatMessage;
use App\Models\Core\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffChatController extends Controller
{
    public function staff(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureStoreChatEnabled($user);
        $canChatAcrossBranches = $this->storeHasChatEntitlement((int) $user->store_id, 'admin.chats.cross-branch');
        $staff = User::query()->with(['role:id,display_name', 'branch:id,name'])->where('store_id', $user->store_id)->where('is_active', true)
            ->when(!$canChatAcrossBranches, fn($query) => $query->where('branch_id', $user->branch_id))
            ->whereKeyNot($user->id)->orderBy('fname')->orderBy('lname')->get(['id','fname','lname','email','avatar_path','role_id','branch_id']);
        return response()->json(['success' => true, 'data' => $staff]);
    }

    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureStoreChatEnabled($user);
        $rows = StaffChatConversation::query()->where('store_id', $user->store_id)->whereHas('participants', fn($q) => $q->where('users.id', $user->id))
            ->with(['participants' => fn($q) => $q->where('users.id', '!=', $user->id)->select('users.id','fname','lname','email','avatar_path'), 'messages' => fn($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at')->get()->map(function ($conversation) use ($user) {
                $other = $conversation->participants->first(); $last = $conversation->messages->first();
                $readAt = DB::table('staff_chat_participants')->where('conversation_id', $conversation->id)->where('user_id', $user->id)->value('last_read_at');
                $unread = StaffChatMessage::where('conversation_id', $conversation->id)->where('sender_user_id', '!=', $user->id)->when($readAt, fn($q) => $q->where('created_at', '>', $readAt))->count();
                return ['id'=>$conversation->id,'staff'=>$other,'last_message'=>$last?->message ?: ($last?->attachment_name ? 'Attachment: '.$last->attachment_name : 'No messages yet'),'last_message_at'=>$conversation->last_message_at,'unread_count'=>$unread];
            });
        return response()->json(['success'=>true,'data'=>$rows]);
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate(['user_id'=>['required','integer']]); $user=$request->user();
        $this->ensureStoreChatEnabled($user);
        $other=User::whereKey($data['user_id'])->where('store_id',$user->store_id)->where('is_active',true)->firstOrFail();
        abort_if($other->id === $user->id, 422, 'Select another staff member.');
        abort_if(!$this->storeHasChatEntitlement((int) $user->store_id, 'admin.chats.cross-branch') && (int)$other->branch_id !== (int)$user->branch_id, 403, 'This store plan only allows chats within the same branch.');
        $conversation = StaffChatConversation::where('store_id',$user->store_id)
            ->whereHas('participants',fn($q)=>$q->where('users.id',$user->id))->whereHas('participants',fn($q)=>$q->where('users.id',$other->id))->has('participants','=',2)->first();
        if (!$conversation) DB::transaction(function () use (&$conversation,$user,$other) { $conversation=StaffChatConversation::create(['store_id'=>$user->store_id]); $conversation->participants()->attach([$user->id,$other->id]); });
        return response()->json(['success'=>true,'data'=>['id'=>$conversation->id]],201);
    }

    public function messages(Request $request, int $conversationId): JsonResponse
    {
        $conversation=$this->conversation($request,$conversationId);
        DB::table('staff_chat_participants')->where('conversation_id',$conversation->id)->where('user_id',$request->user()->id)->update(['last_read_at'=>now(),'updated_at'=>now()]);
        $messages=$conversation->messages()->with('sender:id,fname,lname,avatar_path')->latest()->paginate(min(max((int)$request->input('per_page',80),1),150));
        return response()->json(['success'=>true,'data'=>$messages]);
    }

    public function send(Request $request, int $conversationId): JsonResponse
    {
        $conversation=$this->conversation($request,$conversationId);
        $data=$request->validate(['message'=>['nullable','string','max:4000'],'attachment'=>['nullable','file','max:10240','mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt,zip']]);
        abort_if(trim((string)($data['message']??''))==='' && !$request->hasFile('attachment'),422,'Enter a message or attach a file.');
        $file=$request->file('attachment'); $path=$file?->store('staff-chat/'.$conversation->store_id,'private');
        $message=StaffChatMessage::create(['conversation_id'=>$conversation->id,'sender_user_id'=>$request->user()->id,'message'=>trim((string)($data['message']??'')) ?: null,'attachment_path'=>$path,'attachment_name'=>$file?->getClientOriginalName(),'attachment_mime'=>$file?->getMimeType(),'attachment_size'=>$file?->getSize()]);
        $conversation->update(['last_message_at'=>$message->created_at]);
        return response()->json(['success'=>true,'data'=>$message->load('sender:id,fname,lname,avatar_path')],201);
    }

    public function attachment(Request $request, StaffChatMessage $message): StreamedResponse
    {
        $this->conversation($request,(int)$message->conversation_id); abort_unless($message->attachment_path && Storage::disk('private')->exists($message->attachment_path),404);
        return Storage::disk('private')->download($message->attachment_path,$message->attachment_name);
    }

    private function conversation(Request $request,int $id): StaffChatConversation
    {
        $this->ensureStoreChatEnabled($request->user());
        return StaffChatConversation::whereKey($id)->where('store_id',$request->user()->store_id)->whereHas('participants',fn($q)=>$q->where('users.id',$request->user()->id))->firstOrFail();
    }

    private function ensureStoreChatEnabled(User $user): void
    {
        abort_unless($this->storeHasChatEntitlement((int) $user->store_id, 'admin.chats.manage'), 403, 'Staff chat is not included in this store plan.');
    }

    private function storeHasChatEntitlement(int $storeId, string $permission): bool
    {
        if ($storeId <= 0) {
            return false;
        }

        return DB::table('stores')
            ->join('plan_permissions', 'plan_permissions.plan_id', '=', 'stores.subscription_tier')
            ->join('permissions', 'permissions.id', '=', 'plan_permissions.permission_id')
            ->where('stores.id', $storeId)
            ->where('permissions.name', $permission)
            ->where('permissions.is_active', true)
            ->whereNull('permissions.deleted_at')
            ->where('plan_permissions.included', true)
            ->exists();
    }
}
