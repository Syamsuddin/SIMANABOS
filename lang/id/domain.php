<?php

return [
    'report' => [
        'stale' => 'Laporan diubah orang lain. Perubahan Anda belum tersimpan; muat ulang halaman.',
        'unauthorized_actor' => 'Peran Anda tidak berwenang melakukan perubahan status ini.',
        'not_claimer' => 'Hanya pemeriksa yang mengambil laporan ini yang dapat melanjutkannya.',
        'already_claimed' => 'Laporan sudah diambil pemeriksa lain.',
        'withdraw_claimed' => 'Laporan sudah diambil pemeriksa dan tidak dapat ditarik kembali.',
        'principal_approval_missing' => 'Persetujuan kepala sekolah belum ada. Minta kepala sekolah menyetujui laporan sebelum diajukan.',
        'notes_unanswered' => 'Masih ada catatan pemeriksa yang belum ditanggapi. Tanggapi setiap catatan lalu ajukan kembali.',
        'notes_open' => 'Masih ada catatan terbuka. Tandai semua catatan selesai sebelum menyatakan laporan selesai diperiksa.',
        'no_new_note' => 'Tambahkan minimal satu catatan sebelum meminta perbaikan.',
        'opening_balance_unverified' => 'Saldo awal tahun pertama belum diverifikasi. Verifikasi saldo awal lebih dahulu.',
        'unlock_reason_required' => 'Alasan buka kunci wajib diisi.',
        'year_closed' => 'Tahun anggaran sudah ditutup; laporan tidak dapat dibuka kuncinya.',
        'locked' => 'Laporan tidak dapat diubah pada status ini.',
    ],
    'fiscal_year' => [
        'already_closed' => 'Tahun anggaran sudah ditutup.',
        'next_missing' => 'Tahun anggaran :year belum dibuka. Buka tahun berikutnya sebelum menutup tahun ini.',
        'confirm_year' => 'Masih ada laporan Desember yang belum selesai diperiksa. Ketik ulang tahun anggaran (:year) untuk menutup.',
    ],
    'note' => [
        'not_open' => 'Catatan hanya dapat ditambah, ditanggapi, atau diselesaikan saat laporan sesuai tahapnya.',
        'already_resolved' => 'Catatan sudah ditandai selesai.',
    ],
    'principal' => [
        'disabled' => 'Persetujuan kepala sekolah tidak diaktifkan.',
        'not_editable' => 'Laporan hanya dapat disetujui saat berstatus Draf atau Perlu perbaikan.',
    ],
];
