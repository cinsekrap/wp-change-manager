<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundingRoundItem extends Model
{
    protected $fillable = ['funding_round_id', 'change_request_id', 'estimated_hours'];

    public function fundingRound()
    {
        return $this->belongsTo(FundingRound::class);
    }

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class);
    }
}
