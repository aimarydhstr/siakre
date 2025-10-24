<?php
namespace App\Exports;

use App\Models\article;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class dataExportArticle implements FromView
{
   private $category;
   use Exportable;
   public function setMahasiswa(){
     $this->category = "mahasiswa";
   }
   public function setDosen(){
      $this->category = "dosen";
    }
   public function view(): View
    {
      //  dd(  $this->category );
      $data = article::all();
       
      //    dd(articles::selection());
      
      $data_type_array  = ["Seminar Nasional","Seminar Internasional","Jurnal Internasional","Jurnal Internasional Bereputasi","Jurnal Nasional Terakreditasi","Jurnal Nasional Tidak Terakreditasi"];
      $TS_array = [];
      $TS_1_array= [];
      $TS_2_array= [];
      $val_TS = 0;
      $val_TS_1= 0;
      $val_TS_2 = 0;
      
      foreach($data_type_array  as $data_types){
         foreach($data as $data_all){
                  $date = $data_all->date;
                
                  $year = date ( 'Y' ,$date);
                  // dd($year);
                  $month =(int) date ( 'm' ,$date );
                  
                  $type = $data_all->type_journal;

                  if($data_all->category==$this->category){
                    
                     // dd($type);
                     if ($data_types == $type){
                        // dd(  $month ,$year );
                           if ( ($month <=8 && $year == "2023" )|| ($month >=9 && $year == "2022") ){
                           $val_TS ++;
                           
                     }
                     if(($month <=8 && $year == "2022" )|| ($month >=9 && $year == "2021" )){
                              $val_TS_1 ++;
                     }
                     if(($month <=8 && $year == "2021" )|| ($month >=9 && $year == "2020" )){
                           $val_TS_2 ++;
                           }
                     }
                  }
               }
               $TS_array [] =$val_TS;
               $TS_1_array [] =$val_TS_1;
               $TS_2_array [] =$val_TS_2;
               $val_TS = 0;
               $val_TS_1 = 0;
               $val_TS_2 = 0;

      }

      // dd($TS_array,$TS_1_array,$TS_2_array);
      return view('excel.article',
         ['data_type_array'=>$data_type_array,
         'TS_array'=>$TS_array,
         'TS_1_array'=>$TS_1_array,
         'TS_2_array'=>$TS_2_array
      ]);
    }
}