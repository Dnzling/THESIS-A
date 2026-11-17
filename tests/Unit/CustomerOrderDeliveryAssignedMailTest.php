<?php

use App\Mail\CustomerOrderDeliveryAssignedMail;

uses(Tests\TestCase::class);

it('renders the trip departure and requested delivery dates in the customer email', function () {
    $mail = new CustomerOrderDeliveryAssignedMail([
        'customer_name' => 'Jamie Customer',
        'order_number' => 'POS-20261115-0001',
        'store_name' => 'Northwood Living',
        'trip_reference' => 'TRIP-2026-000012',
        'tracking_number' => 'LGS-20261115-0001',
        'tracking_url' => 'https://furnisync.shop/track-order?tracking_number=LGS-20261115-0001',
        'departure_date' => 'November 16, 2026',
        'delivery_date' => 'November 17, 2026',
    ]);

    $html = $mail->render();

    expect($html)
        ->toContain('POS-20261115-0001')
        ->toContain('TRIP-2026-000012')
        ->toContain('Track your order')
        ->toContain('LGS-20261115-0001')
        ->toContain('November 16, 2026')
        ->toContain('November 17, 2026');
});
