<?php
/**
 * Base class for every web page. Each page is its own subclass in its own file.
 * Lifecycle (template method):  guard → onPost (if POST) → beforeRender → layout → render
 */
abstract class Page
{
    // Who may open the page
    public const PUBLIC = 'public';   // anyone
    public const GUEST  = 'guest';    // logged-out visitors only
    public const MEMBER = 'member';   // logged-in members (admins are sent to the admin view)
    public const STAFF  = 'staff';    // library staff / IT (each page also names a permission)
    public const ANY    = 'any';      // any logged-in user

    /** @var array<string,string> field => message, filled by validation */
    protected array $errors = [];

    public function __construct(protected string $slug) {}

    abstract protected function access(): string;
    abstract protected function title(): string;
    abstract protected function render(): void;

    /** Optional permission name (see Permissions) a staff member needs for this page. */
    protected function permission(): ?string { return null; }

    /** 'app' (navigation bar) or 'auth' (centered card). */
    protected function layout(): string { return 'app'; }
    /** Which nav item to highlight. */
    protected function navSlug(): string { return $this->slug; }
    protected function onPost(): void {}
    protected function beforeRender(): void {}

    final public function run(): void
    {
        $this->guard();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify()) {
                Session::flash('error', 'Your session expired. Please try again.');
                $this->redirect($this->slug, $_GET);
            }
            $this->onPost();
        }

        $this->beforeRender();
        Layout::open($this->title(), $this->layout(), $this->navSlug());
        $this->render();
        Layout::close($this->layout());
    }

    private function guard(): void
    {
        $level = $this->access();
        if ($level === self::PUBLIC) return;

        if ($level === self::GUEST) {
            if (Auth::check()) $this->redirect(Auth::isStaff() ? 'admin_dashboard' : 'home');
            return;
        }
        if (!Auth::check()) $this->redirect('login');

        if ($level === self::STAFF) {
            if (!Auth::isStaff()) {
                Session::flash('error', 'You do not have permission to view that page.');
                $this->redirect('home');
            }
            $perm = $this->permission();
            if ($perm !== null && !Auth::can($perm)) {
                Session::flash('error', 'Your role (' . Permissions::label(Auth::role()) . ') cannot open that page.');
                $this->redirect('admin_dashboard');
            }
        }
        if ($level === self::MEMBER && Auth::isStaff()) $this->redirect('admin_dashboard');
    }

    // ---------- helpers for subclasses ----------

    protected function redirect(string $page, array $params = []): never
    {
        unset($params['page']);
        header('Location: ' . url($page, $params));
        exit;
    }

    protected function error(string $field): string
    {
        return isset($this->errors[$field]) ? '<div class="field-error">' . e($this->errors[$field]) . '</div>' : '';
    }

    /** For POST handlers: stop with an error unless the current role holds the permission. */
    protected function requireCan(string $perm): void
    {
        if (!Auth::can($perm)) {
            Session::flash('error', 'Your role is not allowed to do that.');
            $this->redirect('admin_dashboard');
        }
    }

    /** Fetch a positive integer id from POST. */
    protected function postId(string $key = 'id'): int { return max(0, (int)($_POST[$key] ?? 0)); }
}
