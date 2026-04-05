<html>
  <body>
    <p>Hi {{ $name }},</p>
    <p>Your supplier account has been created on {{ $system_name ?? 'the system' }} for store: {{ $store_name ?? 'N/A' }}.</p>
    <p><strong>User ID:</strong> {{ $user_id }}</p>
    <p><strong>Password:</strong> {{ $password }}</p>
    <p>You can login here: <a href="{{ $login_url }}">{{ $login_url }}</a></p>
    <p>Please change your password after first login. The password is only shown in this email.</p>
    <p>Regards,<br/>{{ $system_name ?? 'The Team' }}</p>
  </body>
</html>
