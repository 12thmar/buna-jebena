<?php
class ContactInbound extends Mailable {
  use Queueable, SerializesModels;
  public function __construct(public array $data) {}
  public function build() {
    return $this->subject('New Website Inquiry')
      ->replyTo($this->data['email'], $this->data['name'])
      ->view('emails.contact_inbound')
      ->with($this->data);
  }
}
