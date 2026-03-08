<?php 
 
namespace App\Models;

use App\Models\Core\User as CoreUser;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends CoreUser{

}