<?php
class ContactAutoReply extends Mailable {
  use Queueable, SerializesModels;
  public function __construct(public array $data) {}
  public function build() {
    return $this->subject('Thanks — BunaRoots Sales')
      ->to($this->data['email'], $this->data['name'])
      ->view('emails.contact_autoreply')
      ->with($this->data);
  }
}
