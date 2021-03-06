<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests;
use App\Movie;
use App\Rating;
use App\User;
use App\Http\Controllers\Controller;
use App\Http\Controllers\RatingsController;
use App\Http\Controllers\ReviewsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\DB;

class MoviesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function getMovieById(Request $request, $id = null) {
        $user_id = $request->session()->get('user_id', false);
        $q = $request->input('q');
        if ($q) {
            $result = Movie::whereRaw("name like '%" . $q . "%'")->get();
            return self::makeResponse($result, 200, '', '');
        }
        if ($id == null) {
            $result = Movie::orderBy('date_released', 'des')->get();
        } else {
            $result = $this->show($id);
            $result['reviews'] = (new ReviewsController)->getReviewsByMovie($id);
            if ($user_id) {
                $result['user_rated'] = (new RatingsController)->getRatingByUser($user_id);
            }
        }
        return self::makeResponse($result, 200, '', '');
    }

    /**
     * Add a newly created resource into storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function addNewMovie(Request $request) {
        $movie = new Movie;
        $data = $request->input('data', false);
        if (!$data) {
            return self::makeResponse([], 400, 'Missing data.', '');
        }
        $movie->timestamps = false;
        foreach ($data as $key => $value) {
            if ($movie->$key !== $value) {
                $movie->$key = $value;
            }
        }
        $movie->save();

        return self::makeResponse(array('id' => $movie->id), 200, 'New movie added', '');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id) {
        return Movie::find($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function updateMovie(Request $request, $id) {
        $movie = Movie::find($id);
        $is_update = $request->input('update', false);
        $user = DB::table('users')->where('id', $request->session()->get('user_id'))->first();
        $data = $request->input('data');
        $points = $request->input('points');

        if (!$user) {
            return self::makeResponse([], 401, 'Unauthorized user.', '');
        }

        if (!$movie) {
            return self::makeResponse([], 404, 'Not Found', '');
        }

        if ($is_update) {
            if ($user->role == 32) {
                if (!$data) {
                    return self::makeResponse([], 400, 'Missing data.', '');
                }
                foreach ($data as $key => $value) {
                    if ($movie->$key !== $value) {
                        $movie->$key = $value;
                    }
                }
            } else { // user rates movie
                if (!$points) {
                    return self::makeResponse([], 400, 'Data "points" is required.', '');
                }
                // insert rating to rating table
                $result = (new RatingsController)->addRating($movie->id, $user->id, $points);
                if ($result['id']) {
                    $movie->rating = number_format($this->getRating($movie->id), 1);
                } else {
                    return self::makeResponse([], 500, 'Internal Server Error', '');
                }
            }
            $movie->timestamps = false;
            $movie->save();
            return self::makeResponse(array('id' => $movie->id), 200, '', '');
        } else {
            if ($user->role == 32) {
                $movie->delete();
                return self::makeResponse(array('id' => $movie->id), 200, 'Deleted.', '');
            } else {
                return self::makeResponse([], 403, 'Access denied.', '');
            }
        }
    }

    public function getReviews(Request $request, $movie_id) {
        $reviews = (new ReviewsController)->getReviewsByMovie($movie_id);
        return self::makeResponse(array('reviews' => $reviews), 200, '', '');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    // public function deleteMovie(Request $request) {
    //     $movie = Movie::find($request->input('id'));

    //     $movie->delete();

    //     return self::makeResponse(array('id' => $movie->id), 200, '', '');
    // }

    /** 
     * Get the average rating of the movie
    */
    private function getRating($movie_id) {
        $average = DB::table('ratings')
            ->join('movies', 'movies.id', '=', 'ratings.movie_id')
            ->avg('points');
        return $average;
    }
}