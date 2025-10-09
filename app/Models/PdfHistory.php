<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfHistory extends Model
{
    use HasFactory;

    protected $table = 'pdf_history';

    protected $fillable = [
        'ocorrencyId',
        'pdf_path',
        'uploadDate',
        'evidencyUploader',
        'reason',
        'reasonDateTime',
        'evidencyApprover',
    ];
}
