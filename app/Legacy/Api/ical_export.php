<?php

declare(strict_types=1);

function lh_ical_export_escape_text(string $s): string
{
    $s = str_replace('\\', '\\\\', $s);
    $s = str_replace(["\r\n", "\r", "\n"], '\n', $s);
    $s = str_replace(';', '\;', $s);
    $s = str_replace(',', '\,', $s);

    return $s;
}

function lh_ical_export_emit_line(string $name, string $value): string
{
    $line = $name . ':' . $value;
    $out = '';
    $first = true;
    while ($line !== '') {
        $limit = $first ? 75 : 74;
        $len = strlen($line);
        if ($len <= $limit) {
            $out .= $first ? $line : ("\r\n " . $line);
            $out .= "\r\n";

            return $out;
        }
        $chunk = substr($line, 0, $limit);
        $line = substr($line, $limit);
        $out .= $first ? $chunk : ("\r\n " . $chunk);
        $first = false;
    }

    return $out;
}

/**
 * @return array{status: int, body: string, content_type: string, content_disposition?: string}
 */
function lh_api_ical_export(string $token): array
{
    if ($token === '') {
        return [
            'status' => 404,
            'body' => 'Invalid token',
            'content_type' => 'text/plain; charset=utf-8',
        ];
    }

    global $conn;

    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, title FROM properties WHERE ical_export_token = ? LIMIT 1'
    );

    if ($stmt === false) {
        return [
            'status' => 500,
            'body' => 'Database error',
            'content_type' => 'text/plain; charset=utf-8',
        ];
    }

    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $property = lh_mysqli_stmt_fetch_assoc($stmt);
    mysqli_stmt_close($stmt);

    if (!$property) {
        return [
            'status' => 404,
            'body' => 'Property not found',
            'content_type' => 'text/plain; charset=utf-8',
        ];
    }

    $property_id = (int) $property['id'];

    $res = mysqli_query(
        $conn,
        'SELECT bd.start_date, bd.end_date, bd.source, bd.external_event_id, bd.notes,
                b.guest_name, b.guest_phone
         FROM blocked_dates bd
         LEFT JOIN bookings b
           ON b.property_id = bd.property_id
          AND b.check_in = bd.start_date
          AND b.check_out = bd.end_date
         WHERE bd.property_id = ' . $property_id . "
           AND bd.source IN ('direct_booking', 'manual_block')
         ORDER BY bd.start_date ASC"
    );

    if ($res === false) {
        return [
            'status' => 500,
            'body' => 'Database error',
            'content_type' => 'text/plain; charset=utf-8',
        ];
    }

    $output = "BEGIN:VCALENDAR\r\n";
    $output .= "VERSION:2.0\r\n";
    $output .= "PRODID:-//LIKEHOME//ICAL EXPORT//EN\r\n";
    $output .= "CALSCALE:GREGORIAN\r\n";

    while ($row = mysqli_fetch_assoc($res)) {
        $start = date('Ymd', strtotime((string) $row['start_date']));
        $end = date('Ymd', strtotime((string) $row['end_date']));

        $extId = trim((string) ($row['external_event_id'] ?? ''));
        if ($extId !== '' && strlen($extId) <= 200 && preg_match('/^[A-Za-z0-9._@-]+$/', $extId)) {
            $uid = strpos($extId, '@') !== false ? $extId : ($extId . '@likehome');
        } else {
            $uid = md5($property_id . $start . $end . ($row['source'] ?? '')) . '@likehome';
        }

        $output .= "BEGIN:VEVENT\r\n";
        $output .= lh_ical_export_emit_line('UID', lh_ical_export_escape_text($uid));
        $output .= lh_ical_export_emit_line('DTSTART;VALUE=DATE', $start);
        $output .= lh_ical_export_emit_line('DTEND;VALUE=DATE', $end);

        $title = (string) ($property['title'] ?? 'Property');

        if (($row['source'] ?? '') === 'direct_booking') {
            $guest = (string) ($row['guest_name'] ?? '');
            $phone = (string) ($row['guest_phone'] ?? '');
            $summary = 'Reserved - ' . $title;
            $output .= lh_ical_export_emit_line('SUMMARY', lh_ical_export_escape_text($summary));

            if ($guest !== '' || $phone !== '') {
                $desc = 'Booking via website';
                if ($guest !== '') {
                    $desc .= ' | Guest: ' . $guest;
                }
                if ($phone !== '') {
                    $desc .= ' | Phone: ' . $phone;
                }
                $output .= lh_ical_export_emit_line('DESCRIPTION', lh_ical_export_escape_text($desc));
            }
        } else {
            $summary = 'Manual Block - ' . $title;
            $output .= lh_ical_export_emit_line('SUMMARY', lh_ical_export_escape_text($summary));
            $notes = trim((string) ($row['notes'] ?? ''));
            if ($notes !== '') {
                $output .= lh_ical_export_emit_line('DESCRIPTION', lh_ical_export_escape_text($notes));
            }
        }

        $output .= "END:VEVENT\r\n";
    }

    $output .= "END:VCALENDAR\r\n";

    return [
        'status' => 200,
        'body' => $output,
        'content_type' => 'text/calendar; charset=utf-8',
        'content_disposition' => 'inline; filename="calendar.ics"',
    ];
}
