<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'cr',
        'ocorrency',
        'origin',
        'action',
        'startDate',
        'dueDate',
        'email',
        'pdf_path',
        'confirmEvidency',
        'reason',
        'emailCreator',
        'lastPdfUpload',     // 🔹 nova coluna
        'evidencyUpploader', // 🔹 nova coluna
        'evidencyApprover',  // 🔹 nova coluna
        'nameCreator',
        'reasonDateTime',
    ];
}