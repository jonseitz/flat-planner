<?php

use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class User extends Eloquent implements UserInterface, RemindableInterface {

	use UserTrait, RemindableTrait;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'users';

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('password', 'remember_token');

	/**
	 *	The attributes that should not be mass assigned.
	 *
	 *	@var array
	 */
	protected $guarded = array('id', 'created_at', 'updated_at');

	public function assignments() {
		return $this->hasMany('Assignment');
	}

	public function roles() {
		return $this->hasMany('Role');
	}
}
