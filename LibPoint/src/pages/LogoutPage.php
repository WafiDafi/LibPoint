<?php
final class LogoutPage extends Page
{
    protected function access(): string { return self::PUBLIC; }
    protected function title(): string  { return 'Logout'; }

    protected function onPost(): void
    {
        Auth::logout();
        Session::start(); // fresh session so the flash message survives
        Session::flash('success', 'You have been logged out.');
        $this->redirect('login');
    }

    protected function beforeRender(): void { $this->redirect('home'); } // plain GET: nothing to show
    protected function render(): void {}
}
