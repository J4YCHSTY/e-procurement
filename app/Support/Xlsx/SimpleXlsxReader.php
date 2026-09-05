<?php

namespace App\Support\Xlsx;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Pembaca file .xlsx yang minimalis, TANPA dependency Composer tambahan
 * (cuma pakai ZipArchive + SimpleXML bawaan PHP).
 *
 * Sengaja dibikin sendiri (bukan pakai phpoffice/phpspreadsheet) karena
 * command employees:import ini harus tetap jalan di mesin manapun tanpa
 * perlu composer require dulu. File .xlsx sebenarnya cuma arsip zip berisi
 * beberapa file XML - class ini baca bagian yang perlu doang: daftar
 * sheet, shared string table, dan isi baris/kolom dari satu sheet.
 *
 * Cuma dukung format .xlsx modern (Excel 2007+, termasuk hasil export
 * Google Sheets). Nggak dukung .xls lama atau file yang di-lock/encrypt.
 */
class SimpleXlsxReader
{
    /**
     * Baca semua baris dari satu sheet, dikembalikan sebagai array
     * of array (list angka biasa, index kolom mulai dari 0). Baris
     * pertama biasanya header - itu urusan pemanggil buat mapping.
     *
     * @return list<list<string|null>>
     */
    public static function rows(string $path, ?string $sheetName = null): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException("Gagal membuka file: {$path}. Pastikan ini file .xlsx yang valid.");
        }

        try {
            $sharedStrings = self::readSharedStrings($zip);
            $sheetPath = self::findSheetPath($zip, $sheetName);

            return self::readSheetRows($zip, $sheetPath, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    private static function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $sx = new SimpleXMLElement($xml);
        $strings = [];
        foreach ($sx->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
            } else {
                // Rich text (beda format per potongan kata) - gabung semua <r><t>.
                $text = '';
                foreach ($si->r as $run) {
                    $text .= (string) $run->t;
                }
                $strings[] = $text;
            }
        }

        return $strings;
    }

    private static function findSheetPath(ZipArchive $zip, ?string $sheetName): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            throw new RuntimeException('Struktur file .xlsx tidak lengkap (workbook.xml/rels tidak ketemu).');
        }

        $workbook = new SimpleXMLElement($workbookXml);
        $rels = new SimpleXMLElement($relsXml);

        $relationshipIdToTarget = [];
        foreach ($rels->Relationship as $rel) {
            $relationshipIdToTarget[(string) $rel['Id']] = (string) $rel['Target'];
        }

        $relationshipNamespace = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

        $chosenId = null;
        $firstId = null;
        foreach ($workbook->sheets->sheet as $sheet) {
            $name = (string) $sheet['name'];
            $id = (string) $sheet->attributes($relationshipNamespace)['id'];

            $firstId ??= $id;

            if ($sheetName !== null && strcasecmp(trim($name), trim($sheetName)) === 0) {
                $chosenId = $id;
                break;
            }
        }

        $id = $chosenId ?? $firstId;

        if ($id === null || ! isset($relationshipIdToTarget[$id])) {
            $requested = $sheetName ?? '(sheet pertama)';
            throw new RuntimeException("Sheet '{$requested}' tidak ketemu di dalam file.");
        }

        $target = ltrim($relationshipIdToTarget[$id], '/');

        return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
    }

    private static function readSheetRows(ZipArchive $zip, string $sheetPath, array $sharedStrings): array
    {
        $xml = $zip->getFromName($sheetPath);
        if ($xml === false) {
            throw new RuntimeException("Isi sheet ({$sheetPath}) tidak ketemu di dalam file.");
        }

        $sx = new SimpleXMLElement($xml);
        $rows = [];

        foreach ($sx->sheetData->row as $row) {
            $cellsByColumn = [];
            $maxColumn = -1;

            foreach ($row->c as $c) {
                $columnIndex = self::columnLetterToIndex((string) $c['r']);
                $type = (string) $c['t'];

                $value = match (true) {
                    $type === 's' => $sharedStrings[(int) $c->v] ?? '',
                    $type === 'inlineStr' => (string) ($c->is->t ?? ''),
                    $type === 'str' => (string) $c->v,
                    isset($c->v) => (string) $c->v,
                    default => null,
                };

                $cellsByColumn[$columnIndex] = $value;
                $maxColumn = max($maxColumn, $columnIndex);
            }

            $paddedRow = [];
            for ($i = 0; $i <= $maxColumn; $i++) {
                $paddedRow[] = $cellsByColumn[$i] ?? null;
            }

            $rows[] = $paddedRow;
        }

        return $rows;
    }

    private static function columnLetterToIndex(string $cellReference): int
    {
        preg_match('/^([A-Z]+)/', $cellReference, $matches);
        $letters = $matches[1] ?? 'A';

        $index = 0;
        foreach (str_split($letters) as $char) {
            $index = $index * 26 + (ord($char) - ord('A') + 1);
        }

        return $index - 1;
    }
}
