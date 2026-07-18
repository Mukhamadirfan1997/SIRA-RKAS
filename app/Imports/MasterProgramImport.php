<?php

namespace App\Imports;

use App\Models\MasterProgram;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasterProgramImport implements WithMultipleSheets
{
    public int $importedCount = 0;
    public int $skippedCount  = 0;
    /** @var array<int, string> */
    private array $rowErrors  = [];

    /**
     * @return array<int, MasterProgramSheetImport>
     */
    public function sheets(): array
    {
        return [
            1 => new MasterProgramSheetImport($this), // Sheet index 1 is "KEGIATAN"
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getAllErrors(): array
    {
        return $this->rowErrors;
    }

    public function addError(string $error): void {
        $this->rowErrors[] = $error;
    }
}

class MasterProgramSheetImport implements ToCollection, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    private ?MasterProgramImport $parent;

    public function __construct(MasterProgramImport $parent)
    {
        $this->parent = $parent;
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            // Header dari sheet KEGIATAN PRD (di-lowercase oleh excel_maatwebsite): 
            // kode_kegiatan, program, sub_program, uraian
            
            $kode = $row['kode_kegiatan'] ?? null;
            $nama = $row['uraian'] ?? null;
            $program = $row['program'] ?? null;
            $subProgram = $row['sub_program'] ?? null;

            if (empty($kode) || empty($nama)) {
                $this->parent->skippedCount++;
                continue;
            }

            try {
                $level = substr_count(trim($kode), '.') + 1;
                
                MasterProgram::updateOrCreate(
                    ['kode' => trim($kode)],
                    [
                        'nama'        => trim($nama),
                        'program'     => trim($program),
                        'sub_program' => trim($subProgram),
                        'parent_id'   => null,
                        'level'       => $level,
                    ]
                );

                $this->parent->importedCount++;
            } catch (\Exception $e) {
                $this->parent->skippedCount++;
                $this->parent->addError('Baris ' . ($index + 2) . ': ' . $e->getMessage());
            }
        }
    }
}
