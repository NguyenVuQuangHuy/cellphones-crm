<?php

function cellphone_warrantly_dates(string $Datedone): array {
    $vietnam = new DateTimeZone('Asia/Ho_Chi_Minh');
    if (str_contains($Datedone, '/')) {
        $start = DateTimeImmutable::createFromFormat('!Y/m/d', $Datedone, $vietnam);
    } else {
        $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $Datedone, new DateTimeZone('UTC'));
    }
    $errors = DateTimeImmutable::getLastErrors();
    if (!$start || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) {
        throw new InvalidArgumentException('Ngày giao phải có dạng YYYY/MM/DD hoặc YYYY-MM-DD HH:MM:SS (UTC).');
    }

    $start = $start->setTimezone($vietnam);
    $end = $start->modify('+365 days');

    return [
        'start' => $start->format('d/m/Y'),
        'end' => $end->format('d/m/Y'),
    ];
}
