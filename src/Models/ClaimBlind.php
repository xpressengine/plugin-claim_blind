<?php
namespace Xpressengine\Plugins\ClaimBlind\Models;

use Xpressengine\Database\Eloquent\DynamicModel;

class ClaimBlind extends DynamicModel
{
    public $table = 'claim_blinds';

    public $incrementing = true;

    public function user()
    {
        return $this->belongsTo('Xpressengine\User\Models\User', 'owner_user_id');
    }
}