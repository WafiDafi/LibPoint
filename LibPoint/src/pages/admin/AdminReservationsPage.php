<?php
/** Admin: see every reservation, mark ready for pickup, or cancel. */
final class AdminReservationsPage extends Page
{
    protected function access(): string { return self::STAFF; }
    protected function permission(): ?string { return 'circulation'; }
    protected function title(): string  { return 'Reservations'; }

    protected function onPost(): void
    {
        $id = $this->postId();
        $do = $_POST['do'] ?? '';

        if ($do === 'ready') {
            ReservationModel::markReady($id)
                ? Session::flash('success', 'Marked ready for pickup.')
                : Session::flash('error', 'Only pending reservations can be marked ready.');
        } elseif ($do === 'cancel') {
            ReservationModel::cancel($id, null)
                ? Session::flash('success', 'Reservation cancelled.')
                : Session::flash('error', 'That reservation cannot be cancelled.');
        } else {
            Session::flash('error', 'Unknown action.');
        }
        $this->redirect('admin_reservations');
    }

    protected function render(): void
    {
        $rows = ReservationModel::all();
        ?>
        <div class="page-title">⏳ All Member Reservations</div>
        <div class="card">
          <?php if (!$rows): ?>
            <div class="empty"><div class="empty-icon">⏳</div>No reservations found.</div>
          <?php else: ?>
          <div class="table-wrap"><table>
            <tr><th>#</th><th>Member</th><th>Book Title</th><th>Reserved</th><th>Availability</th><th>Status</th><th>Actions</th></tr>
            <?php foreach ($rows as $i => $r): $open = in_array($r['status'], ['pending', 'ready'], true); ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><strong><?= e($r['user_name']) ?></strong></td>
              <td><?= e($r['book_title']) ?></td>
              <td><?= e(date('M d, Y h:i A', strtotime($r['reserved_at']))) ?></td>
              <td><?= $r['copies'] > 0 ? '<span class="badge badge-green">In Stock</span>' : '<span class="badge badge-yellow">Waitlisted</span>' ?></td>
              <td><?= ReservationStatus::badge($r['status']) ?></td>
              <td class="nowrap">
                <?php if ($open): ?>
                <form method="POST" action="<?= e(url('admin_reservations')) ?>" class="inline">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <?php if ($r['status'] === 'pending'): ?><button class="btn btn-green btn-sm" name="do" value="ready" type="submit">Mark Ready</button><?php endif; ?>
                  <button class="btn btn-red btn-sm" name="do" value="cancel" type="submit" onclick="return confirm('Cancel this reservation?')">Cancel</button>
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
