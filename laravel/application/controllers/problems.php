<?php

class Problems_Controller extends Base_Controller
{
	public $restful=true;
	
	public function __construct()
	{
		$this->filter('before', 'auth');
	}
	
	public function get_index()
	{
		echo "this is the index page of problems";
	}
	
	public function prepareselector($models, $name)
	{
		$items=array();
		foreach ($models AS $model)
		{
			$items[$model->id] = $model->$name;
		};
		return $items;
	}
	
	public function get_new()
	{
		// get the formats, types, and levels to populate selectors
		
		
		$formatstopass2=$this->prepareselector(Problemformat::get(),'format');
		$typestopass2=$this->prepareselector(Problemtype::get(),'type');
		$levelstopass2=$this->prepareselector(Problemlevel::get(),'level');
		
		$currenttags = Tag::get();
		
		
		//$levels=Problemlevel::get(array('level'));
		return View::make('pages.addproblem')
			->with('formats', $formatstopass2)
			->with('levels', $levelstopass2)
			->with('types', $typestopass2)
			->with('currenttags', $currenttags);
	}
	
	public function upload_attachment($fname, $text, $prob, $userid)
	{
		if (array_get(Input::file($fname), 'tmp_name'))
		{
			$type=File::extension(array_get(Input::File($fname),'name'));
			$name=substr(md5(time()),0,16);
			$attachment1=Input::upload($fname,path('storage').'attachments/',$name.'.'.$type);
			$caption1=Input::get('$text');
			$file1=new Attachment(array('user_id'=>$userid,'link'=>path('storage').'attachments/'.$name.'.'.$type));
			$prob->attachments()->insert($file1,array('description'=>$text)); 
		}
	}
	
	public function upload_attachment2($fname, $text, $prob, $userid)
	{
		if (array_get(Input::file($fname), 'tmp_name'))
		{
			$type=File::extension(array_get(Input::File($fname),'name'));
			//$name=substr(md5(time()),0,16);
			//$attachment1=Input::upload($fname,path('storage').'attachments/',$name.'.'.$type);
			$caption1=Input::get('$text');
			$file1=Attachment::create(array('user_id'=>$userid,'type'=>$type));
			//$file1->save();
			$prob->attachments()->attach($file1->id,array('description'=>$text,'user_id'=>$userid)); 
			$name=md5("ppdb".$file1->id);//I've got to fix this so it gets the attachment id
			//$name.=".$type";
			$name="$name.$type";
			$attachment1=Input::upload($fname,path('storage').'attachments/',$name);
		}
	}
	
	public function post_new()
	{
		$input = Input::all();
		$rules = array(
			'title' => 'required',
			'attachment1' => 'image', 
			'attachment2' => 'image', 
			'attachment3' => 'image', 
		);	
		
		$validation = Validator::make($input, $rules);
		if ($validation->fails())
		{
			return Redirect::to('/')->with_input()->with_errors($validation);
		}
		else
		{	
			
			$title = Input::get('title');
			$content = Input::get('content');
			$answer = Input::get('answer');
			$level = Input::get('level');
			$type = Input::get('type');
			$format = Input::get('format');
			$newtags = Input::get('newtags');		
			$userid=Auth::user()->id;
			$prob = new Problem(array(
				'title' => $title,
				'question' => $content,
				'answer' => $answer,
				'problemtype_id' => $type,
				'problemformat_id' => $format,
				'problemlevel_id' => $level));
			$user=User::find($userid);
			$prob=$user->problems()->insert($prob);
			
	/* 		$prob->problemformat()->insert(array('format'=>$format));
			$prob->problemtype()->insert(array('type'=>$type));
			$prob->problemlevel()->insert(array('level'=>$level)); */
			$newtagarray=explode(',',$newtags);
			$existingtags=Tag::lists('tag', 'id');
			foreach($newtagarray AS $newtag)
			{
				// here I want to deal with duplicates
				// I need a list of existing tags
				// that's the line above with 
				// $existingtags=Tag::lists('tag', 'id')
				// where 'id' is the key
				

				if ($newtag != '')
				{
					$foundid=array_search(trim($newtag), $existingtags);
					if (!($foundid))
					{
						$trimmedtag=trim($newtag);
						$tagmodel=$prob->tags()->insert(array('tag' =>$trimmedtag, 'user_id' => $userid), array('user_id'=>$userid));
					} else {
						$prob->tags()->attach($foundid, array('user_id'=>$userid));
					};
				};

			};
			
			$oldtags = Input::get('tags', 'none');
			if ($oldtags != 'none')
			{

				

				foreach($oldtags AS $oldtag)
				{
					$prob->tags()->attach($oldtag, array('user_id'=>$userid));
				};
			};
			
			//handle attachments
			
			$this->upload_attachment2('attachment1', Input::get('caption1'), $prob, $userid);
			$this->upload_attachment2('attachment2', Input::get('caption2'), $prob, $userid);
			$this->upload_attachment2('attachment3', Input::get('caption3'), $prob, $userid);
			
	
			
			return Redirect::to('problems/new')->with_input()->with('submitworked', true);

			};
	}
	
	
	
	
	public function get_mine()
	{
		$myprobs=Auth::user()->problems;
		return View::make('pages.myproblems')
			->with('probs', $myprobs);
	}
	
	public function get_all()
        {
//		$myprobs=Auth::user()->problems;
//		$myprobs = DB::table('problems')->get();
		$myprobs = Problem::all();
                return View::make('pages.myproblems')
                        ->with('probs', $myprobs);
        }

	public function get_last($num)
	{
		//$myprobs=Problem::order_by('created_at', 'desc')->take(10)->get();
		$myprobs=Problem::order_by('created_at', 'desc')->paginate($num);
		return View::make('pages.myproblemspaginate')
			->with('probs', $myprobs);
	}
	
	
	// Here I want to make view problem set where tags can be added
	
	public function get_view($probid)
	{
		$prob=Problem::find($probid);
		$usedtags=$prob->tags()->lists('id');
		// here's where i need to see if there are any used tags
		if (count($usedtags)==0)
		{
			//this happens if the problem has no tags
			$remainingtags=Tag::all();
			
		} else {
			$remainingtags=Tag::where_not_in('id',$usedtags)->get();
			
		};
		$usedtagmodels=$prob->tags;
		$mine=array();
		$others=array();
		$taggerarray=array();
		$datearray=array();
		foreach ($usedtagmodels AS $tag)
		{
			$tagger=$tag->pivot->user_id;
			$taggerarray[]=$tagger;
			$datearray[]=$tag->pivot->created_at;
			if ($tagger == Auth::user()->id)
			{
				$mine[]=$tag;
			} else {
				$others[]=$tag;
			};
		};
		return View::make('pages.singleproblem')
			->with('prob', $prob)
			->with('mytags', $mine)
			->with('othertags', $others)
			->with('unusedtags', $remainingtags);
	}
	
	public function post_view()
	{
		$userid=Auth::user()->id;
		$probid=Input::get('probid');
		$prob=Problem::Find($probid);
		$newtags = Input::get('newtags', 'none');
		if ($newtags != 'none')
		{
			foreach($newtags AS $newtag)
			{
				$prob->tags()->attach($newtag, array('user_id'=>$userid));
			};
		};
		
		$undotags = Input::get('untags', 'none');
		if ($undotags != 'none')
		{
			foreach($undotags AS $newtag)
			{
				$prob->tags()->detach($newtag);
			};
		};
		
		$newtaglist=Input::get('newtaglist');
		$newtagarray=explode(',',$newtaglist);
		foreach($newtagarray AS $newtag)
		{
			if ($newtag != '')
			{
				$tagmodel=$prob->tags()->insert(array('tag' => trim($newtag), 'user_id' => $userid), array('user_id'=>$userid));
			};
		};
		$newcomment=Input::get('comment');
		if ($newcomment != '')
		{
			$prob->comments()->insert(array('content'=>$newcomment, 'user_id'=>$userid));
		};
		return Redirect::to('problems/view/'.$probid);
	}
	public function get_delete($probid)
	{
		if (Auth::user()->is_active==2)
		{
			$prob=Problem::find($probid);
			return View::make('pages.deletepage')
                        ->with('prob', $prob);
                } else {
                	echo "What the hell are you doing?";
                };
	}
	public function post_delete($probid)
	{
		if (Auth::user()->is_active==2)
		{
			$prob=Problem::Find($probid);
			$prob->attachments()->delete();
			$prob->solutions()->delete();
			$prob->tags()->delete();
			$prob->delete();
		} else {
			echo "who the hell are you?";
		};
		return Redirect::to('home');
	}
}
	
	
