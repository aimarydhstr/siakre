<?php
namespace App\Exports;

use App\Models\data;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class userExport implements FromView
{
    
    private $field ;
    use Exportable;
    public function setAkademik(){
        $this->field ="Akademik";
    }
    public function setNonAkademik(){
        $this->field ="NonAkademik";
    }
    public function view(): View
    {

      $count_year = 0;
      $region_val = 0;
      $national_val = 0;
      $international_val = 0;

      $array_year = [];
      $region_val_array = [];
      $national_val_array = [];
      $international_val_array = [];



      $all = data::all()->sortByDesc("year");

      foreach($all as $val){
          if($val->field ==$this->field){
              if(!in_array( $val->year,$array_year)){
                  $array_year[] = $val->year;
              }
          }
      }

      foreach($array_year as $year_data){

          $count_year +=1;
          $array_competition_region = [];
          $array_competition_national = [];
          $array_competition_international = [];

          foreach($all as $val){

              $level = $val->level;
              $competition = $val->competition;
              $organizer = $val->organizer;
              $year = $val->year;
              $team = $val->team;

              if($val->field ==$this->field){
                  if ($level =="Region"){

                      if(!in_array($competition.$team.$year.$organizer, $array_competition_region)){
                          $array_competition_region [] = $competition.$team.$year.$organizer;

                          if($year_data == $year){
                              $region_val +=1;
                          }
                      }
                  }
                  else if ($level =="National"){

                      if(!in_array($competition.$team.$year.$organizer, $array_competition_national)){
                          $array_competition_national [] = $competition.$team.$year.$organizer;
                       
                          if($year_data == $year){
                              $national_val +=1;
                          }
                      }
                  }
                  else if ($level =="International"){

                      if(!in_array($competition.$team.$year.$organizer, $array_competition_international)){
                          $array_competition_international [] = $competition.$team.$year.$organizer;
                        
                          if($year_data == $year){
                              $international_val +=1;
                          }
                      }
                  }
              }
          }
          $region_val_array[] = $region_val;
          $national_val_array[] = $national_val;
          $international_val_array[] = $international_val;

          // reset nilai
          $region_val = 0;
          $national_val = 0;
          $international_val = 0;
      }
      
      return view('excel.export',[
      'year_array'=>$array_year,
      'region_array' => $region_val_array,
      'national_array' => $national_val_array,
      'international_array' => $international_val_array,
      ]);
    }
}

