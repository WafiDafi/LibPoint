<?php
/** Full borrowing report: totals, top members, mark penalty paid, CSV export. */
final class AdminReportPage extends Page
{
    protected function access(): string { return self::STAFF; }
    protected function permission(): ?string { return 'reports'; }
    protected function title(): string  { return 'Reports'; }

    protected function onPost(): void
    {
        $this->requireCan('fees');
        if (BorrowModel::markPaid($this->postId())) Session::flash('success', 'Penalty marked as paid.');
        else Session::flash('error', 'Nothing to mark as paid for that record.');
        $this->redirect('admin_report');
    }

    protected function beforeRender(): void
    {
        if (($_GET['export'] ?? '') !== 'csv') return;

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="library-report-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8
        fputcsv($out, ['Member', 'Book', 'Book ID', 'Borrowed', 'Due', 'Returned', 'Fee', 'Penalty', 'Date Paid', 'Status']);
        // Prefix risky leading characters so spreadsheets don't run cell contents as formulas.
        $safe = fn($s) => preg_match('/^[=+\-@\t\r]/', (string)$s) ? "'" . $s : (string)$s;
        foreach (BorrowModel::everything() as $r) {
            fputcsv($out, [$safe($r['user_name']), $safe($r['book_title']), $r['copy_code'], $r['date_borrowed'], $r['due_date'],
                           $r['date_returned'], $r['fee'], $r['penalty'], $r['date_paid'], $r['status']]);
        }
        fclose($out);
        exit;
    }

    protected function render(): void
    {
        $s    = BorrowModel::stats();
        $rows = BorrowModel::everything();
        $top  = BorrowModel::topBorrowers(5);
        ?>
        <div class="page-title">📊 Reports</div>

        <div class="stats-grid">
          <div class="stat-card"><span class="stat-num"><?= count($rows) ?></span><span class="stat-lbl">Total Loans</span></div>
          <div class="stat-card"><span class="stat-num c-red"><?= $s['overdue'] ?></span><span class="stat-lbl">Overdue Now</span></div>
          <div class="stat-card"><span class="stat-num c-red"><?= peso($s['unpaid']) ?></span><span class="stat-lbl">Unpaid Penalties</span></div>
          <div class="stat-card"><span class="stat-num c-green"><?= peso($s['collected']) ?></span><span class="stat-lbl">Penalties Collected</span></div>
        </div>

        <div class="card">
          <div class="section-title mb">🏆 Most Active Members</div>
          <?php if (!$top): ?><div class="empty">No loans recorded yet.</div><?php else: ?>
          <div class="table-wrap"><table>
            <tr><th>Rank</th><th>Member</th><th>Total Borrowed</th></tr>
            <?php foreach ($top as $i => $t): ?>
            <tr><td>#<?= $i + 1 ?></td><td><?= e($t['name']) ?></td><td><?= (int)$t['total'] ?> book(s)</td></tr>
            <?php endforeach; ?>
          </table></div>
          <?php endif; ?>
        </div>

        <div class="card">
          <div class="section-header">
            <div class="section-title">📄 Full Report</div>
            <a class="btn btn-green btn-sm" href="<?= e(url('admin_report', ['export' => 'csv'])) ?>">📥 Export CSV</a>
          </div>
          <div class="table-wrap"><table>
            <tr><th>#</th><th>Member</th><th>Book</th><th>Book ID</th><th>Borrowed</th><th>Due</th><th>Returned</th><th>Fee</th><th>Penalty</th><th>Action</th></tr>
            <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><strong><?= e($r['user_name']) ?></strong></td>
              <td><?= e($r['book_title']) ?></td>
              <td><code class="code"><?= e($r['copy_code'] ?? '—') ?></code></td>
              <td><?= e($r['date_borrowed']) ?></td>
              <td><?= e($r['due_date']) ?></td>
              <td><?= e($r['date_returned'] ?? '—') ?></td>
              <td><?= peso($r['fee']) ?></td>
              <td><?= peso($r['penalty']) ?></td>
              <td>
                <?php if ($r['penalty'] > 0 && !$r['date_paid']): ?>
                <form method="POST" action="<?= e(url('admin_report')) ?>" class="inline" onsubmit="return confirm('Mark as paid?')">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-green btn-sm" type="submit">Mark Paid</button>
                </form>
                <?php else: ?>—<?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </table></div>
        </div>
        <?php
    }
}
