<h2>New inquiry from {{ $name }}</h2>
<p><strong>Email:</strong> {{ $email }}</p>
@if(!empty($phone))<p><strong>Phone:</strong> {{ $phone }}</p>@endif
<p><strong>Message:</strong></p>
<p style="white-space:pre-line">{{ $message }}</p>
