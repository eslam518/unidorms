<?php
// Escape output safely
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function format_room_label(?string $buildingName, ?string $roomNumber): string
{
    $building = trim((string) ($buildingName ?? ''));
    $room = trim((string) ($roomNumber ?? ''));

    if ($building === '' && $room === '') {
        return 'Unassigned';
    }

    $building = $building === '' ? '' : $building;
    if ($room === '') {
        return $building;
    }

    return $building === '' ? $room : $building . ' ' . $room;
}

// Set a one-time flash message
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Retrieve and clear the flash message
function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// Recalculate a room's occupied count and status based on current occupancy
function refresh_room_status(PDO $pdo, int $roomId): void
{
    $room = $pdo->prepare('SELECT capacity, status FROM rooms WHERE id = ?');
    $room->execute([$roomId]);
    $room = $room->fetch();
    if (!$room) {
        return;
    }

    $count = $pdo->prepare("SELECT COUNT(*) FROM students WHERE room_id = ? AND status = 'active'");
    $count->execute([$roomId]);
    $occupied = (int) $count->fetchColumn();

    if ($room['status'] === 'maintenance') {
        // Still keep the occupied count accurate, just don't override the maintenance flag
        $update = $pdo->prepare('UPDATE rooms SET occupied = ? WHERE id = ?');
        $update->execute([$occupied, $roomId]);
        return;
    }

    $newStatus = $occupied >= (int) $room['capacity'] ? 'full' : 'available';
    $update = $pdo->prepare('UPDATE rooms SET occupied = ?, status = ? WHERE id = ?');
    $update->execute([$occupied, $newStatus, $roomId]);
}
