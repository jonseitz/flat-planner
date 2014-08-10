<?php

class UserController extends \BaseController {

	private $rules = array(
		'username'=>'required|unique:users,username|unique:organizations,slug',
		'email'=>'required|email|unique:users,email',
		'password'=>'required|confirmed|min:8',
		'image_url'=>'url',
		'website_url'=>'url');
	private $updateRules = array(
		'image_url'=>'url',
		'website_url'=>'url'
		);

	public function __contruct()
	{
		parent::__construct();
		$this->beforeFilter('guest', array('only' => array('create', 'store', 'getLogin', 'postLogin')));
		$this->beforeFilter('auth', array('only' => array('edit', 'update', 'getLogout', 'index')));
	}



	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function getCreateUser()
	{
		return View::make('createUser');
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function postCreateUser()
	{
		$validator = Validator::make(Input::all(), $this->rules);
		if($validator->fails()){
			return Redirect::back()->with('flash_message', 'Sign up failed. Please try again.')->withInput()->withErrors($validator);
		}
		$user = new User();
		$user->first_name = Input::get('first-name');
		$user->last_name = Input::get('last-name');
		$user->username = parent::create_slug(Input::get('username'));
		$user->email = Input::get('email');
		$user->password = Hash::make(Input::get('password'));
		if(Input::has('image_url')){
			$user->image_url = Input::get('image_url');
		}
		else{
			$user->image_url = '/assets/image/logo.svg';
		}
		$user->website_url = Input::get('website_url');
		$user->city = Input::get('city');
		$user->state = Input::get('state');
		$user->country = Input::get('country');
		$user->bio = Input::get('profile');
		try{
			$user->save();
		}
		catch(Exception $e){
			return Redirect::back()->with('flash_message', 'Sign up failed. Please try again.')->withInput();
		}
		//Create a personal organization for each user
		$org = new Organization();
		$org->name = $user->first_name.' '.$user->last_name;
		$org->slug = parent::create_slug(Input::get('username'));
		if(Input::has('image_url')){
			$org->image_url = Input::get('image_url');
		}
		else{
			$org->image_url = '/assets/image/logo.svg';
		}
		$org->website_url = Input::get('website_url');
		$org->description = Input::get('profile');
		$org->city = Input::get('city');
		$org->state = Input::get('state');
		$org->country = Input::get('country');
		try{
			$org->save();
		}
		catch(Exception $e){
			return Redirect::back()->with('flash_message', 'Sign up failed. Please try again.')->withInput();
			}
		//make user the editor of that organization
		$role = new Role();
		$role->title = 'personal project';
		$role->permissions = 'edit';
		$role->organization_id = $org->id;
		$role->user_id = $user->id;
		$role->save();
		Auth::login($user);
		return Redirect::to('/user/'.$user->username)->with('flash_message', 'Update Successful');
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getViewUser($username)
	{
		try{
			$user = User::whereUsername($username)->firstOrFail();
		}
		catch (exception $e) {
			return View::make('fourOhFour');
		}
		$roles = Role::with('organization')->where('user_id', '=', $user->id)->get();
		if(Auth::check() && Auth::user()->id == $user->id){
			$permission = "self";
		}
		else{
			$permission = "other";
		}

		return View::make('viewUser')->with('user', $user)
												->with('roles', $roles)
												->with('permission', $permission);
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getEditUser($username)
	{
		try{
			$user = User::whereUsername($username)->firstOrFail();
		}
		catch (exception $e) {
			return View::make('fourOhFour');
		}
		if(Auth::check() && Auth::user()->id == $user->id){
			return View::make('editUser')->with('user', $user);
		}
		else {
			return Redirect::to('/user/'.$username)->with('flash_message', 'You cannot edit this user');
		}
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function putEditUser($username)
	{
		try{
			$user = User::whereUsername($username)->firstorFail();
		}
		catch (exception $e) {
			return View::make('fourOhFour');
		}
		if(Auth::check() && Auth::user()->id != $user->id){
			return Redirect::to('/user/'/$username)->with('flash_message', 'You cannot edit this user');
		}
		$validator = Validator::make(Input::all(), $this->updateRules);
		if($validator->fails()){
			return Redirect::back()->with('flash_message', 'Edit failed. Please try again.')->withInput()->withErrors($validator);
		}
		$user->first_name = Input::get('first-name');
		$user->last_name = Input::get('last-name');
		if(Input::has('image_url')){
			$user->image_url = Input::get('image_url');
		}
		$user->website_url = Input::get('website_url');
		$user->city = Input::get('city');
		$user->state = Input::get('state');
		$user->country = Input::get('country');
		$user->bio = Input::get('profile');
		$user->save();
		return Redirect::to('/user/'.$username)->with('flash_message', 'Update successful');
	}

	/**
	 *	Displays the login form.
	 *
	 *	@return Response
	 */
	public function getLogin(){
		return View::make('login');
	}

	/**
	 * Handles the login form.
	 *
	 * @return Response
	 */
	public function postLogin(){
		try{
			$user = User::where('username', '=', Input::get('username'))->orWhere('email', '=', Input::get('username'))->firstOrFail();
		}
		catch (Exception $e) {
			return Redirect::to('/login')->withInput()->with('flash_message', 'Invalid username or e-mail. Please try again.');
		}
		if(Hash::check(Input::get('password'), $user->password)){
			Auth::login($user, Input::get('remember'));
			return Redirect::intended('/user/'.$user->username)->with('flash_message', 'Login successful. Welcome back, '.$user->first_name);
		}
		else{
			return Redirect::to('/login')->withInput()->with('flash_message', 'Invalid password');
		}
	}

	/**
	 * Logs out the user
	 *
	 * @return response
	 */
	public function getLogout() {
		Auth::logout();
		return Redirect::to('/')->with('flash_message', 'Logout successful.');
	}
}
