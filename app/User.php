<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function questions() {
      return $this->hasMany(Question::class);
    }

    public function getUrlAttribute() {
      //return route("questions.show", $this->id);
      return '#';
    }

    public function getAvatarAttribute() {
      $email = $this->email;
      $size = 32;

      return "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $email ) ) ) . "?s=" . $size;
    }

    public function favorites() {
      return $this->belongsToMany(Question::class, 'favorites')->withTimestamps();
    }

    public function voteQuestions() {
      return $this->morphedByMany(Question::class, 'votable');
    }

    public function voteAnswers() {
      return $this->morphedByMany(Answer::class, 'votable');
    }

    public function voteQuestion(Question $question, $vote) {
      $voteQuestions = $this->voteQuestions();
      if($voteQuestions->where('votable_id', $question->id)->exists()) {
        $voteQuestions->updateExistingPivot($question, ['vote' => $vote]);
      } else {
        $voteQuestions->attach($question, ['vote' => $vote]);
      }
      $question->load('votex');
      $upVotes = (int)$question->votex()->wherePivot('vote',1)->sum('vote');
      $downVotes = (int)$question->votex()->wherePivot('vote',-1)->sum('vote');

      $question->votes = $upVotes + $downVotes;
      $question->save();
    }
}
