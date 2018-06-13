<?php

namespace Inpin\LaraAlert;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;

/**
 * Trait Alertable.
 *
 * @property-read int alertsCount
 * @property-read bool isAlerted
 */
trait Alertable
{
    /**
     * Boot the soft alertable trait for a model.
     *
     * @return void
     */
    public static function bootAlertable()
    {
        if (static::removeAlertsOnDelete()) {
            static::deleting(function ($model) {
                /* @var Alertable $model */
                $model->removeAlerts();
            });
        }
    }

    /**
     * Fetch records that are alerted by a given user.
     * Ex: Book::whereAlertedBy(123)->get();.
     *
     * @param Builder          $query
     * @param User|string|null $guard
     *
     * @return Builder|static
     */
    public function scopeWhereAlertedBy($query, $guard = null)
    {
        if (!($guard instanceof User)) {
            $guard = $this->getLoggedInUserForLaraAlert($guard);
        }

        return $query->whereHas('alerts', function ($query) use ($guard) {
            /* @var Builder $query */
            $query->where('user_id', '=', $guard->id);
        });
    }

    /**
     * Populate the $model->alertsCount attribute.
     */
    public function getAlertsCountAttribute()
    {
        return $this->alertsCount();
    }

    /**
     * Collection of the alerts on this record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function alerts()
    {
        return $this->morphMany(Alert::class, 'alertable');
    }

    /**
     * This method will create an alert on current model, attach given alertItemIds, and return it.
     *
     * @param string      $type
     * @param User|string $guard
     * @param null|string $description
     *
     * @return Alert|null
     */
    public function createAlert($type = 'alert', $guard = null, $description = null)
    {
        if (!($guard instanceof User)) {
            $guard = $this->getLoggedInUserForLaraAlert($guard);

            if (is_null($guard)) {
                return null;
            }
        }

        $alert = new Alert([
            'user_id'     => $guard->id,
            'type'        => $type,
            'description' => $description,
        ]);

        /** @var Alert $alert */
        $alert = $this->alerts()->save($alert);

        return $alert;
    }

    /**
     * This method will delete alerts on current model, attach given alertItemIds, and return it.
     * If guard is null, then it will delete all alerts, else will delete alerts for specific user.
     *
     * @param string $type
     * @param User|string $guard
     *
     * @return bool
     */
    public function deleteAlert($type = 'alert', $guard = null)
    {
        if (!($guard instanceof User) && is_string($guard)) {
            $guard = $this->getLoggedInUserForLaraAlert($guard);

            if (is_null($guard)) {
                return false;
            }
        }

        return $this->alerts()
            ->where('type', $type)
            ->when(!is_null($guard), function (Builder $query) use ($guard) {
                $query->where('user_id', $guard->id);
            })
            ->delete();
    }

    /**
     * Has the currently logged in user already "alerted" the current object.
     *
     * @param string|User $guard - The guard of current user, If instance of Illuminate\Foundation\Auth\User use as user
     *
     * @return bool
     */
    public function isAlertedBy($guard = null)
    {
        if (!($guard instanceof User)) {
            $guard = $this->getLoggedInUserForLaraAlert($guard);

            if (is_null($guard)) {
                return false;
            }
        }

        return $this->alerts()
            ->where('user_id', '=', $guard->id)
            ->exists();
    }

    /**
     * Determines if current object is alerted or not.
     *
     * @return bool
     */
    public function isAlerted()
    {
        return $this->alerts()->exists();
    }

    /**
     * Retrieve number of alerts.
     *
     * @return int
     */
    public function alertsCount()
    {
        return $this->alerts()->count();
    }

    /**
     * Fetch the primary ID of the currently logged in user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function getLoggedInUserForLaraAlert($guard)
    {
        return auth($guard)->user();
    }

    /**
     * Did the currently logged in user alert this model.
     * Example : if($book->isAlerted) { }.
     *
     * @return bool
     */
    public function getIsAlertedAttribute()
    {
        return $this->isAlerted();
    }

    /**
     * Should remove alerts on model row delete (defaults to true).
     * public static removeAlertsOnDelete = false;.
     */
    public static function removeAlertsOnDelete()
    {
        return isset(static::$removeAlertsOnDelete)
            ? static::$removeAlertsOnDelete
            : true;
    }

    /**
     * Delete alerts related to the current record.
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function removeAlerts()
    {
        return Alert::query()
            ->where('alertable_type', $this->getMorphClass())
            ->where('alertable_id', $this->id)
            ->delete();
    }
}
