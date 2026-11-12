<?php
/** Renders the coloured badge for a reservation status. */
final class ReservationStatus
{
    public static function badge(string $status): string
    {
        $class = ['pending' => 'badge-yellow', 'ready' => 'badge-blue', 'claimed' => 'badge-green'][$status] ?? 'badge-gray';
        return '<span class="badge ' . $class . '">' . e(ucfirst($status)) . '</span>';
    }
}
