<?php

return [
    'common' => [
        'greeting' => 'Hi :name',
    ],

    'receipt' => [
        'subject' => 'Resit Derma Anda — :name',
        'title' => 'Terima kasih atas derma anda!',
        'intro' => 'Derma anda berjumlah :amount kepada :campaign telah diterima dengan jayanya.',
        'recurring_note' => 'Ini adalah derma berulang. Anda akan menerima resit bagi setiap pembayaran yang berjaya.',
        'donation' => 'Derma',
        'amount' => 'Jumlah',
        'processing_fee' => 'Yuran Pemprosesan',
        'total_charged' => 'Jumlah Dicaj',
        'campaign' => 'Kempen',
        'organization' => 'Organisasi',
        'date' => 'Tarikh',
        'payment_method' => 'Kaedah Pembayaran',
        'status' => 'Status',
        'successful' => 'Berjaya',
        'closing' => 'Terima kasih atas sokongan anda!',
        'donor_portal_cta' => 'Pergi ke portal donor anda',
        'donor_portal_text' => 'untuk melihat sejarah derma, urus langganan, dan muat turun resit.',
    ],

    'dunning' => [
        'subject_final' => 'Peluang Terakhir Untuk Kemaskini Pembayaran — :name',
        'subject_almost_final' => 'Percubaan Akhir Esok — :name',
        'subject_default' => 'Pembayaran Gagal — Kemaskini Kad Anda — :name',
        'heading_final' => 'Peluang Terakhir Bulan Ini',
        'heading_almost_final' => 'Percubaan Akhir Esok',
        'heading_default' => 'Pembayaran Gagal',
        'intro_final' => 'Ini adalah percubaan akhir untuk memproses derma berulang anda bagi :campaign bulan ini.',
        'intro_almost_final' => 'Esok adalah percubaan akhir untuk memproses derma berulang anda bagi :campaign.',
        'intro_default' => 'Kami tidak dapat memproses derma berulang anda bagi :campaign.',
        'campaign' => 'Kempen',
        'amount' => 'Jumlah',
        'frequency' => 'Kekerapan',
        'attempt' => 'Percubaan',
        'update_payment' => 'Kemaskini Kaedah Pembayaran',
        'already_updated' => 'Jika anda sudah mengemaskini kad, tiada tindakan diperlukan — Stripe akan cuba semula secara automatik.',
        'final' => 'akhir',
        'attempt_note' => 'Ini adalah percubaan ke-:retry daripada :total bagi tempoh bil ini.',
    ],

    'subscription_amount_changed' => [
        'subject' => 'Jumlah Langganan Dikemaskini — :name',
        'title' => 'Jumlah Langganan Dikemaskini',
        'intro' => 'Derma berulang bagi :campaign telah dikemaskini daripada :previous kepada :amount setiap :interval.',
        'donor' => 'Donor',
        'previous_amount' => 'Jumlah Sebelum',
        'new_amount' => 'Jumlah Baharu',
        'frequency' => 'Kekerapan',
        'campaign' => 'Kempen',
        'date' => 'Tarikh',
        'reason' => 'Anda menerima email ini kerana jumlah derma berulang telah dikemaskini.',
        'donor_portal_cta' => 'Pergi ke portal donor anda',
        'donor_portal_text' => 'untuk melihat sejarah derma, urus langganan, dan muat turun resit.',
    ],

    'campaign_completed' => [
        'subject' => 'Sasaran Tercapai — :campaign',
        'title' => 'Sasaran Tercapai!',
        'intro' => 'Terima kasih atas sokongan anda! Kempen :campaign oleh :organization telah berjaya mencapai sasaran :amount.',
        'thanks' => 'Setiap derma yang anda berikan membantu menjayakan kempen ini. Kami amat menghargai sumbangan anda.',
        'reason' => 'Anda menerima email ini kerana telah menderma kepada kempen ini.',
    ],

    'magic_link' => [
        'title' => ':name — Portal Donor',
        'intro' => 'Klik butang di bawah untuk akses portal donor anda di mana anda boleh melihat sejarah derma, urus langganan, dan muat turun resit.',
        'button' => 'Akses Portal Donor',
        'expiry' => 'Pautan ini tamat tempoh dalam 24 jam. Jika anda tidak memintanya, sila abaikan email ini.',
    ],
];
