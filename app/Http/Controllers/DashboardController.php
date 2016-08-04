<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Usuario as Usuario;

use Input, Response,  Validator;
use Illuminate\Http\Response as HttpResponse;


class DashboardController extends Controller {

	protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }
    
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(){
	}
}