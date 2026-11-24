<?php
declare(strict_types=1);

const APP_NAME = 'Perumahan RAB';
const APP_VERSION = '1.0';

define('DATA_DIR', __DIR__ . '/data');
define('PROJECT_DIR', DATA_DIR . '/projects');

/** Jenis fasilitas umum: kunci => [label, kode harga borongan per m², warna] */
const FASUM_JENIS = [
    'taman'    => ['Taman / RTH',            'F01', '#86c98d'],
    'ibadah'   => ['Tempat ibadah',          'F02', '#c9a86a'],
    'pos'      => ['Pos jaga / kantor',      'F03', '#b39ddb'],
    'olahraga' => ['Lapangan olahraga',      'F04', '#7fc4c9'],
    'parkir'   => ['Parkir bersama',         'F05', '#b8bcc4'],
    'lainnya'  => ['Fasum lainnya',          'F06', '#e0b48a'],
];

/** Palet warna untuk tipe rumah */
const WARNA_TIPE = ['#4f9d69', '#3b82c4', '#d9822b', '#8b5cf6', '#e0527a', '#14b8a6', '#a3a33a', '#c25b5b'];
