<?php

namespace Inpin\LaraAlert;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

/**
 * Class Alert.
 *
 * @property int user_id
 * @property Carbon seen_at
 * @property string description
 * @property string user_message
 * @property User user
 * @property Model Alertable
 * @property-read  bool isSeen
 * @property-read  bool isNew
 */
class Alert extends Model
{
    protected $table = 'laraalert_alerts';
    public $timestamps = true;
    protected $dates = ['seen_at'];
    protected $fillable = [
        'user_id',
        'type',
        'description',
    ];

    /**
     * Retrieve alertable model belongs to current alert.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function alertable()
    {
        return $this->morphTo();
    }

    /**
     * Retrieve user who is belongs to current alert.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(
            'Illuminate\Foundation\Auth\User',
            'user_id',
            'id'
        );
    }

    /**
     * Seen current alert, (fill 'seen_at' with current timestamp).
     *
     * @return bool
     */
    public function seen()
    {
        $this->seen_at = Carbon::now();

        return $this->save();
    }

    /**
     * Determines if current alert is new.
     *
     * @return bool
     */
    public function isNew()
    {
        return is_null($this->seen_at);
    }

    /**
     * Populate the $alert->isNew attribute.
     */
    public function getIsNewAttribute()
    {
        return $this->isNew();
    }

    /**
     * Check if current alert is seen or not.
     *
     * @return bool
     */
    public function isSeen()
    {
        return !$this->isNew();
    }

    /**
     * Populate the $alert->isSeen attribute.
     */
    public function getIsSeenAttribute()
    {
        return $this->isSeen();
    }
}
