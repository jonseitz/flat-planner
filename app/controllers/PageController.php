<?php

class PageController extends \BaseController {

	private $rules = array(
		'image'=>'url',
		'slug'=>'max:15'
		);

	public function __contruct()
	{
		parent::__construct();
		$this->beforeFilter('auth');		
	}


/**
 * Creates a new page
 * 
 *	@param $slug slug of the organization
 * @param $plan slug of the plan
 * @return response
 */

	public function getCreatePage($slug, $plan) {
		try{
			$org = Organization::where('slug', '=', $slug)->firstOrFail();
			$flatplan = Flatplan::where('organization_id', '=', $org->id)->where('slug', '=', $plan)->with('pages')->firstOrFail();
		}
		catch(Exception $e) {
			return View::Make('fourOhFour');
		}
		if(Auth::check()){
			try{
				$role = Role::where('organization_id', '=', $org->id)->where('user_id', '=', Auth::user()->id)->firstOrFail();
			}
			catch(Exception $e){
				return Redirect::to('/'.$org->slug)->with('flash_message', 'you do not have permission to view this flatplan.'); 
			}
			if($role->permissions == 'edit')
				return View::make('createPage')->with('org', $org)->with('flatplan', $flatplan);
			else{
				return Redirect::to('/'.$org->slug.'/'.$flatplan->slug)->with('flash_message', 'You do not have permission to add pages to this flatplan.');
				}
		}
		else{
			return Redirect::to('/login');
		}
	}

/**
 * Handles the creation of a page
 *
 *	@param $slug slug of the organization
 * @param $plan slug of the plan
 * @return response
 */

	public function postCreatePage($slug, $plan) {
		try{
			$org = Organization::where('slug', '=', $slug)->firstOrFail();
			$flatplan = Flatplan::where('organization_id', '=', $org->id)->where('slug', '=', $plan)->with('pages')->firstOrFail();
		}
		catch(Exception $e) {
			return View::Make('fourOhFour');
		}
		$validator = Validator::make(Input::all(), $this->rules);
		if ($validator->fails()){
			return Redirect::back()->withInput()->withErrors($validator)->with('flash_message', 'There were errors with your page. See below.');
		}
		else{
			$page = new Page();
			$page->page_number = count($flatplan->pages)-3;
			if(Input::get('slug')){
				$page->slug = Input::get('slug');
			}
			else{ 
				$page->slug = ' ';
			}
			$page->notes = Input::get('notes');
			$page->color = Input::get('color');
			$page->image_url = Input::get('image');
			$page->flatplan_id = $flatplan->id;
			$page->save();
			$pageOpp = new Page();
			$pageOpp->page_number = count($flatplan->pages)-2;
			$pageOpp->slug = Input::get('slug');
			$pageOpp->notes = Input::get('notes');
			$pageOpp->color = Input::get('color');
			$pageOpp->image_url = Input::get('image');
			$pageOpp->flatplan_id = $flatplan->id;
			$pageOpp->save();
			$page->spread_page_id = $pageOpp->id;
			$page->save();
			$pageOpp->spread_page_id = $page->id;
			$pageOpp->save();
			parent::renumber_pages($flatplan);
			return Redirect::to('/'.$org->slug.'/'.$flatplan->slug)->with('flash_message', 'Page added');
		}
	}

/**
 * View a single page
 *
 *	@param $slug slug of the organization
 * @param $plan slug of the flatplan
 * @param $page number of the page
 *	@return response
 */
	public function getViewPage ($slug, $plan, $number){
		try{
			$org = Organization::where('slug', '=', $slug)->firstOrFail();
			$flatplan = Flatplan::where('organization_id', '=', $org->id)->where('slug', '=', $plan)->with('pages')->firstOrFail();
			$page = Page::where('flatplan_id', '=', $flatplan->id)->where('page_number', '=', $number)->firstOrFail();
			if($page->spread_page_id){
				$pageOpp = Page::where('flatplan_id', '=', $flatplan->id)->where('id', '=', $page->spread_page_id)->firstOrFail();
			}
			$assignments = Assignment::where('page_id', '=', $page->id)->orWhere('page_id', '=', $pageOpp->id)->get();
		}
		catch(Exception $e) {
			return View::make('fourOhFour');
		}
		$members = parent::member_list($slug);
		try{
			$role = Role::where('user_id', '=', Auth::user()->id)->where('organization_id', '=', $org->id)->firstOrFail();
		}
		catch (Exception $e) {
			return Redirect::to('/'.$org->slug)->with('flash_message', 'You do not have permission to view this flatplan');
		}
		if($role->permissions == 'edit'){
			$readonly = '';
		}
		else{
			$readonly = 'disabled';
		}
		if($page->spread_page_id){
			return View::make('viewPage')->with('org', $org)
													->with('flatplan', $flatplan)
													->with('page', $page)
													->with('pageOpp', $pageOpp)
													->with('assignments', $assignments)
													->with('members', $members)
													->with('permission', $role->permissions)
													->with('readonly', $readonly);
		}
		else {
			return View::make('viewPage')->with('org', $org)
													->with('flatplan', $flatplan)
													->with('page', $page)
													->with('assignments', $assignments)
													->with('members', $members)
													->with('permission', $role->permissions)
													->with('readonly', $readonly);
		}
	}

/**
 * Edit a single page's information
 *
 * @param $slug slug of the organization
 * @param $plan slug of the flatplan
 * @param $page number of the page
 * @return response
 */
	public function getEditPage ($slug, $plan, $number){
	try{
			$org = Organization::where('slug', '=', $slug)->firstOrFail();
			$flatplan = Flatplan::where('organization_id', '=', $org->id)->where('slug', '=', $plan)->with('pages')->firstOrFail();
			$page = Page::where('flatplan_id', '=', $flatplan->id)->where('page_number', '=', $number)->firstOrFail();
			if($page->spread_page_id){
				$pageOpp = Page::where('flatplan_id', '=', $flatplan->id)->where('id', '=', $page->spread_page_id)->firstOrFail();
			}
		}
		catch(Exception $e) {
			return View::make('fourOhFour');
		}
		try{
			$role = Role::where('user_id', '=', Auth::user()->id)->where('organization_id', '=', $org->id)->firstOrFail();
		}
		catch (Exception $e) {
			return Redirect::to('/'.$org->slug)->with('flash_message', 'You do not have permission to edit this flatplan');
		}
		if($role->permissions == 'edit'){
			if($page->spread_page_id){
				return View::make('editPage')->with('org', $org)->with('flatplan', $flatplan)->with('page', $page)->with('pageOpp', $pageOpp);
			}
			else{
				return View::make('editPage')->with('org', $org)->with('flatplan', $flatplan)->with('page', $page);
			}
		}
		else{
			return Redirect::to('/'.$org->slug.'/'.$flatplan->slug.'/'.$page->page_number)->with('flash_message', 'You do not have permission to edit this page');
		}
	}

/**
 * Handle edits to a single page's information
 *
 * @param $slug slug of the organization
 * @param $plan slug of the flatplan
 * @param $page number of the page
 * @return response
 */
	public function putEditPage ($slug, $plan, $number){
		try{
			$org = Organization::where('slug', '=', $slug)->firstOrFail();
			$flatplan = Flatplan::where('organization_id', '=', $org->id)->where('slug', '=', $plan)->with('pages')->firstOrFail();
			$page = Page::where('flatplan_id', '=', $flatplan->id)->where('page_number', '=', $number)->firstOrFail();
		}
		catch(Exception $e) {
			return View::make('fourOhFour');
		}
		$validator = Validator::make(Input::all(), $this->rules);
		if ($validator->fails()){
			return Redirect::back()->withInput()->withErrors($validator)->with('flash_message', 'There were errors with your page. See below.');
		}
		else{	
			$page->slug = Input::get('slug');
			$page->notes = Input::get('notes');
			$page->color = Input::get('color');
			$page->copy = Input::get('copy');
			$page->edit = Input::get('edit');
			$page->art = Input::get('art');
			$page->design = Input::get('design');
			$page->approve = Input::get('approve');
			$page->proofread = Input::get('proofread');
			$page->close = Input::get('close');
			$page->image_url = Input::get('image');
			$page->save();
			return Redirect::to('/'.$org->slug.'/'.$flatplan->slug.'/'.$page->page_number)->with('flash_message', 'Page updated successfully');
		}
	}

/**
 * Displays the page to confirm deleting an assignment
 *
 *	@param $slug slug of the organization
 * @param $plan slug of the flatplan
 * @param $number page number to be assigned
 * @param $id the id of the assignment
 * @return response
 */
	public function getConfirmDelete ($slug, $plan, $number){
		try{
			$org = Organization::where('slug', '=', $slug)->firstOrFail();
			$flatplan = Flatplan::where('organization_id', '=', $org->id)->where('slug', '=', $plan)->firstOrFail();
			$page = Page::where('flatplan_id', '=', $flatplan->id)->where('page_number', '=', $number)->firstOrFail();
			$pageOpp = Page::where('spread_page_id', '=', $page->id)->firstOrFail();
		}
		catch(Exception $e) {
			return View::make('fourOhFour');
		}
		if ($page->page_number == 'COVER' || $page->page_number == 'IFC' 
				|| $page->page_number == 'IBC' || $page->page_numnber == 'BACK'
				|| $pageOpp->page_number == 'COVER' || $pageOpp->page_number == 'IFC' 
				|| $pageOpp->page_number == 'IBC' || $pageOpp->page_numnber == 'BACK'){
			return Redirect::to('/'.$org->slug.'/'.$flatplan->slug)->with('flash_message', 'You cannot delete cover pages');
		}
		try{
			$role = Role::where('organization_id', '=', $org->id)->where('user_id', '=', Auth::user()->id)->firstOrFail();
		}
		catch (Exception $e){
			return Redirect::to('/'.$org->slug)->with('flash_message', 'You do not have permission to edit this organization');
		}
		if($role->permissions == 'edit'){
			$action = 'delete pages '.$page->page_number.' and '.$pageOpp->page_number;
			$additional = 'This will also delete all associated assignments';
			$back = '/'.$org->slug.'/'.$flatplan->slug;
			$url = '/'.$org->slug.'/'.$flatplan->slug.'/'.$page->page_number.'/delete';
			return View::make('confirmDelete')->with('action', $action)
															->with('additional', $additional)
															->with('back', $back)
															->with('url', $url);
		}
		else{
			return Redirect::to('/'.$org->slug.'/'.$flatplan->slug.'/'.$page->page_number)->with('flash_message', 'You do not have permission to delete this page');
		}
	}

/**
 * handle deleting an assignment
 *
 *	@param $slug slug of the organization
 * @param $plan slug of the flatplan
 * @param $number page number to be assigned
 * @param $id the id of the assignment
 * @return response
 */

	public function deletePage ($slug, $plan, $number){
		try{
			$org = Organization::where('slug', '=', $slug)->firstOrFail();
			$flatplan = Flatplan::where('organization_id', '=', $org->id)->where('slug', '=', $plan)->firstOrFail();
			$page = Page::where('flatplan_id', '=', $flatplan->id)->where('page_number', '=', $number)->firstOrFail();
			$pageOpp = Page::where('spread_page_id', '=', $page->id)->firstOrFail();
		}
		catch(Exception $e) {
			return View::make('fourOhFour');
		}
		if ($page->cover == true || $pageOpp->cover == true){
			return Redirect::to('/'.$org->slug.'/'.$flatplan->slug)->with('flash_message', 'You cannot delete cover pages');
		}
		try{
			$role = Role::where('organization_id', '=', $org->id)->where('user_id', '=', Auth::user()->id)->firstOrFail();
		}
		catch (Exception $e){
			return Redirect::to('/'.$org->slug)->with('flash_message', 'You do not have permission to edit this organization');
		}
		if($role->permissions == 'edit'){
			$assignments = Assignment::where('page_id', '=', $page->id)->orWhere('page_id', '=', $pageOpp->id)->get();
			if($assignments){
				foreach($assignments as $assignment){
					$assignment->delete();
				}
			}
			DB::statement('SET FOREIGN_KEY_CHECKS = 0');
			$page->delete();
			$pageOpp->delete();
			DB::statement('SET FOREIGN_KEY_CHECKS = 1');
			parent::renumber_pages($flatplan);
			return Redirect::to('/'.$org->slug.'/'.$flatplan->slug)->with('flash_message', 'Page deleted successfully');
		}
		else{
			return Redirect::to('/'.$org->slug.'/'.$flatplan->slug.'/'.$page->page_number)->with('flash_message', 'You do not have permission to delete this page');
		}	
	}
}
