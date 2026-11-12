<?php
/** Member's own reservations, with cancel. */
final class ReservationsPage extends Page
{
    protected function access(): string { return self::MEMBER; }
    protected function title(): string  { return 'My Reservations'; }

    protected function onPost(): void
    {
        if (ReservationModel::cancel($this->postId(), Auth::id())) Session::flash('success', 'Reservation cancelled.');
        else Session::flash('error', 'That reservation cannot be cancelled.');
        $this->redirect('reservations');
    }

    protected function render(): void
    {
        $rows = ReservationModel::forUser(Auth::id());
        ?>
        <div class="page-title">⏳ My Book Reservations</div>
        <div class="card">
          <?php if (!$rows): ?>
            <div class="empty"><div class="empty-icon">⏳</div>No reservations found.</div>
          <?php else: ?>
          <div class="table-wrap"><table>
            <tr><th>#</th><th>Book Title</th><th>Reserved Date</th><th>Availability</th><th>Status</th><th>Action</th></tr>
            <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= e($r['book_title']) ?></td>
              <td><?= e(date('M d, Y h:i A', strtotime($r['reserved_at']))) ?></td>
              <td><?= $r['copies'] > 0 ? '<span class="badge badge-green">In Stock</span>' : '<span class="badge badge-yellow">Waitlisted</span>' ?></td>
              <td><?= ReservationStatus::badge($r['status']) ?></td>
              <td>
                <?php if (in_array($r['status'], ['pending', 'ready'], true)): ?>
                <form method="POST" action="<?= e(url('reservations')) ?>" class="inline" onsubmit="return confirm('Cancel this reservation?')">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-red btn-sm" type="submit">Cancel</button>
                </form>
                <?php else: ?>—<?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </table></div>
          <?php endif; ?>
        </div>
        <?php
    }
}
