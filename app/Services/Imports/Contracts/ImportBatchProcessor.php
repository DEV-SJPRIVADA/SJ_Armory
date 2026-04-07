<?php

namespace App\Services\Imports\Contracts;

use App\Models\User;
use App\Models\WeaponImportRow;

interface ImportBatchProcessor
{
    public function type(): string;

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array{row_number:int, cells: array<int, string>}>  $rows
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, int>}
     */
    public function prepareRows(array $headers, array $rows): array;

    public function executeRow(WeaponImportRow $row, User $user): void;
}
