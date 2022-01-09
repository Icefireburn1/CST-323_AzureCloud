<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use DB;
use Illuminate\Support\Facades\Storage;

// Monolog
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

use Monolog\Handler\LogglyHandler;
use Monolog\Formatter\LogglyFormatter;

$logger = new Logger('my_logger');
$errlogger = new Logger('my_err_logger');

$logger->pushHandler(new StreamHandler(storage_path().'/logs/my_app.log', Logger::INFO));
$errlogger->pushHandler(new StreamHandler(storage_path().'/logs/my_app_err.log', Logger::ERROR));
$logger->pushHandler(new FirePHPHandler());

class PostsController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['index', 'show']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = Post::all();

        return view('posts.index')->with('posts', $posts);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('posts.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        global $logger;

        $this->validate($request, [
            'title' => 'required',
            'body' => 'required',
            'cover_image' => 'image|nullable|max:1999'
        ]);

        if ($request->hasFile('cover_image')) {
            $filenameWithExt = $request->file('cover_image')->getClientOriginalName();

            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);

            $extension = $request->file('cover_image')->getClientOriginalExtension();

            $fileNameToStore = $filename . '_' . time() . '.' . $extension;

            $path = $request->file('cover_image')->storeAs('public/cover_images', $fileNameToStore);
        }
        else {
            $fileNameToStore = 'noimage.jpeg';
            $logger->info('No image uploaded');
        }

        $post = new Post;
        $post->title = $request->input('title');
        $post->body = $request->input('body');
        $post->user_id = auth()->user()->id;
        $post->cover_image = $fileNameToStore;
        $post->save();
        $logger->info('Post created');
        return redirect('/posts')->with('success', 'Post Created');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $post = Post::find($id);
        return view('posts.show')->with('post', $post);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        global $errlogger;
        $post = Post::find($id);
        if (auth()->user()->id !== $post->user_id) {
            $errlogger->error('User not authorized to edit post');
            return redirect('/posts')->with('error', 'Unauthorized Page');
        }

        return view('posts.edit')->with('post', $post);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        global $logger;
        $this->validate($request, [
            'title' => 'required',
            'body' => 'required',
            'cover_image' => 'image|nullable|max:1999'
        ]);

        if ($request->hasFile('cover_image')) {
            $filenameWithExt = $request->file('cover_image')->getClientOriginalName();

            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);

            $extension = $request->file('cover_image')->getClientOriginalExtension();

            $fileNameToStore = $filename . '_' . time() . '.' . $extension;

            $path = $request->file('cover_image')->storeAs('public/cover_images', $fileNameToStore);
        }

        $post = Post::find($id);
        $post->title = $request->input('title');
        $post->body = $request->input('body');
        if ($request->hasFile('cover_image')) {
            $post->cover_image = $fileNameToStore;
        }
        $post->save();
        $logger->info('Post updated');
        return redirect('/posts')->with('success', 'Post Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        global $logger, $errlogger;
        $post = Post::find($id);

        if (auth()->user()->id !== $post->user_id) {
            $errlogger->error('User not authorized to delete post');
            return redirect('/posts')->with('error', 'Unauthorized Page');
        }

        if ($post->cover_image != 'noimage.jpeg') {
            $logger->info('Deleting image');
            Storage::delete('public/cover_images/' . $post->cover_image);
        }


        $post->delete();
        $logger->info('Post deleted');
        return redirect('/posts')->with('success', 'Post Removed');
    }
}
