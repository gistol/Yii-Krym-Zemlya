<?php
Yii::import('zii.widgets.jui.CJuiDatePicker');
class FFJuiDatePicker extends CJuiDatePicker
{
    /**
    * Range name, specified in case the widget is used with another one
    * to allow user to select from - to dates
    *
    * @var string
    */
    public $range='';
	public $cssFile =  null;

    public function init() {
        parent::init();

        $this->options = CMap::mergeArray(array(
                'dateFormat'=>Yii::app()->getLocale()->getDateFormat('short'),
                'changeMonth'=>true,
                'changeYear'=>true,
        ), $this->options);
    }

    public function run()
    {
        /*echo*/ $dateFormat = $this->options['dateFormat'];

        list($name,$id)=$this->resolveNameID();

        if(isset($this->htmlOptions['id']))
            $id=$this->htmlOptions['id'];
        else
            $this->htmlOptions['id']=$id;
        if(isset($this->htmlOptions['name']))
            $name=$this->htmlOptions['name'];
        else
            $this->htmlOptions['name']=$name;

        if ($this->range != '') {
            $this->options['beforeShow'] = <<<EOD
js:function(input, inst) {
	inst.dpDiv.addClass('NoYearDatePicker');

    /*$('.hasDatepicker.{$this->range}').each(function(index, elm){
        if (index == 0) from = elm;
        if (index == 1) to = elm;
    })

    if (to.id == input.id) to = null;
    if (from.id == input.id) from = null;
    if (to) {
        //this one is a 'from' date
        maxDate = $(to).val(); //$.datepicker.parseDate('{$dateFormat}', $(to).val());
        if (maxDate)
            $(inst.input).datepicker("option", "maxDate", maxDate);
    }
    if (from) {
        //this one is a 'to' date
        minDate = $(from).val(); //$.datepicker.parseDate('{$dateFormat}', $(from).val());
        if (minDate)
            $(inst.input).datepicker("option", "minDate", minDate);
    }*/
}
EOD;

		$this->options['onClose'] = <<<EOD
js:function(dateText, inst){
	inst.dpDiv.removeClass('NoYearDatePicker');

	$('.hasDatepicker.{$this->range}').each(function(index, elm){
        if (index == 0) {
        	var date = $("#Seasonalprices_dateStart").datepicker("getDate");
        	if (date) {
				var day = ("0" + date.getDate()).slice(-2);
				var month = ("0" + (date.getMonth() + 1)).slice(-2);
				var year = date.getFullYear();
				$('#Seasonalprices_date_start_formatting').val(day + '-' + month);
	
				var endDate = $("#Seasonalprices_dateEnd").datepicker("getDate");
				if (!endDate) { /* no date is selected manually */
					$('#Seasonalprices_dateEnd').datepicker("setDate", new Date(date.getFullYear(),date.getMonth(),date.getDate()));
					/*$('#Seasonalprices_dateEnd').datepicker("setDate", new Date(date.getFullYear(),date.getMonth(),01));*/
				}
			}

		}
        if (index == 1) {
        	var date = $("#Seasonalprices_dateEnd").datepicker("getDate");
        	if (date) {
				var day = ("0" + date.getDate()).slice(-2);
				var month = ("0" + (date.getMonth() + 1)).slice(-2);
				var year = date.getFullYear();
				$('#Seasonalprices_date_end_formatting').val(day + '-' + month);
			}
		}
    })
}
EOD;
            $this->range = ' '.$this->range;
            if (isset($this->htmlOptions['class']))
                $this->htmlOptions['class'].=$this->range;
            else
                $this->htmlOptions['class']=$this->range;
        }

        if($this->hasModel())
            echo CHtml::activeTextField($this->model,$this->attribute,$this->htmlOptions);
        else
            echo CHtml::textField($name,$this->value,$this->htmlOptions);


        $options=CJavaScript::encode($this->options);

        $js = "jQuery('#{$id}').datepicker($options);";

        if (isset($this->language) && $this->language != 'en'){
            $this->registerScriptFile($this->i18nScriptFile);
            $js = "jQuery('#{$id}').datepicker(jQuery.extend({showMonthAfterYear:false}, jQuery.datepicker.regional['{$this->language}'], {$options}));";
        }
		
		if ($this->htmlOptions['setDatepickerDate'] && $this->htmlOptions['datepickerDateStart']) {
			$js = $js."var queryDate = '".$this->htmlOptions['datepickerDateStart']."'; var dateParts = queryDate.match(/(\d+)/g); var realDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]); $('#Seasonalprices_dateStart').datepicker('setDate', realDate);";
		}
		
		if ($this->htmlOptions['setDatepickerDate'] && $this->htmlOptions['datepickerDateEnd']) {
			$js = $js."var queryDate = '".$this->htmlOptions['datepickerDateEnd']."'; var dateParts = queryDate.match(/(\d+)/g); var realDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]); $('#Seasonalprices_dateEnd').datepicker('setDate', realDate);";
		}
		
        $js = $js."\n\$('body').ajaxSuccess(function(){".$js."})";

        $cs = Yii::app()->getClientScript();
        $cs->registerScript(__CLASS__,     $this->defaultOptions?'jQuery.datepicker.setDefaults('.CJavaScript::encode($this->defaultOptions).');':'');
        $cs->registerScript(__CLASS__.'#'.$id, $js);
    }
}