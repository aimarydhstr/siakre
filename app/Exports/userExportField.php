<?php
namespace App\Exports;

use App\Models\data;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;

class userExportField implements FromView
{
    
    private $field ;
    private $fieldType ;
    private $year;
    use Exportable;

    public function setAkademikRegion(){
        $this->fieldType ="Akademik";
        $this->field ="Region";
        $this ->year = session('akademikSESSION');
    }
    public function setAkademikNational(){
      $this->fieldType ="Akademik";
      $this->field ="National";
      $this ->year = session('akademikSESSION');
  }
   public function setAkademikInternational(){
        $this->fieldType ="Akademik";
        $this->field ="International";
        $this ->year = session('akademikSESSION');
   }
   public function setNonAkademikRegion(){
      $this->fieldType ="NonAkademik";
      $this->field ="Region";
      $this ->year = session('nonAkademikSESSION');
   }
   public function setNonAkademikNational(){
      $this->fieldType ="NonAkademik";
      $this->field ="National";
      $this ->year = session('nonAkademikSESSION');
   }
   public function setNonAkademikInternational(){
      $this->fieldType ="NonAkademik";
      $this->field ="International";
      $this ->year = session('nonAkademikSESSION');
   }

    public function view(): View
    {
      //searching method dan show method
      $data_level = ['level','=',$this->field];
      $data_fieldType = ['field','=',$this->fieldType];
      $request_year = ['year','=',$this->year];

      $data = data::where([$data_level,$data_fieldType,$request_year])
          ->orWhere([$data_level,$data_fieldType, $request_year])
          ->orWhere([$data_level,$data_fieldType, $request_year])
          ->orderBy('id','DESC')->paginate();
      return view('excel.field',[
      'data'=>$data,
      ]);
    }
}
