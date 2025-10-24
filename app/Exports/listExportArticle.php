<?php
namespace App\Exports;

use App\Models\article;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class listExportArticle implements FromView
{

  private $type ;
  use Exportable;
  // ["Seminar Nasional","Seminar Internasional","Jurnal Internasional",
  // "Jurnal Internasional Bereputasi","Jurnal Nasional Terakreditasi","Jurnal Nasional Tidak Terakreditasi"];
  public function setsn(){
    $this->type ="Seminar Nasional";
  }
  public function setsi(){
    $this->type ="Seminar Internasional";
  }
  public function setji(){
    $this->type ="Jurnal Internasional";
  }
  public function setjib(){
    $this->type ="Jurnal Internasional Bereputasi";
  }
  public function setjnt(){
    $this->type ="Jurnal Nasional Terakreditasi";
  }
  public function setjntt(){
    $this->type ="Jurnal Nasional Tidak Terakreditasi";
  }
  public function view(): View
  {
    $data_article = article::where('type_journal',$this->type)->get();
    return view('excel.article_list',[
      'data_article'=> $data_article,
      ]);
  }

}