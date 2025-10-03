<?php

declare(strict_types=1);

namespace App\Commands;

final class VisitorDetailsExportCommand extends Command
{
    public function __main(): int
    {
        $json = file_get_contents($this->base_dir . '/logs/huge_visitor.json');
        $data = json_decode($json, true);

        // Header XML untuk Excel Spreadsheet
        $xmlHeader = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <Workbook
            xmlns="urn:schemas-microsoft-com:office:spreadsheet"
            xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:excel"
            xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
            xmlns:html="http://www.w3.org/TR/REC-html40">
            <Worksheet ss:Name="Sheet1">
                <Table>
        XML;

        // Footer XML
        $xmlFooter = <<<XML
                </Table>
            </Worksheet>
        </Workbook>
        XML;

        $xmlContent = '';
        $xmlContent .= '<Row>';

        $headers = ['NIK', 'BPJS', 'Nama', 'Jenis Kelamin',  'Tanggal Lahir', 'Alamat'];
        foreach ($headers as $header) {
            $xmlContent .= '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>';
        }
        $xmlContent .= '</Row>';

        foreach ($data as $row) {
            $xmlContent .= '<Row>';
            foreach ($row as $value) {
                $xmlContent .= '<Cell><Data ss:Type="String">' . htmlspecialchars($value) . '</Data></Cell>';
            }
            $xmlContent .= '</Row>';
        }

        $xml = $xmlHeader . $xmlContent . $xmlFooter;

        return false === file_put_contents($this->base_dir . '/logs/huge_visitor.xml', $xml) ? 0 : 1;
    }
}
