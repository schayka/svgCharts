<?php

class SvgChart
{

    /**
     * @var array
     * array of data collections,
     * collection structure: array(
     *     'data' => array(val1, val2, ...),
     * )
     */

    public $series = array();

    public $cssClass = '';
    public $canvasLeftPadding = 20;
    public $canvasBottomPadding = 20;
    public $chartStepWidth = 55;
    public $chartHeight = 150;
    public $chartMaxValue;
    public $chartMinValue;
    public $chartValuesDelta;
    public $chartScaleDivision;

    public function getInstance(): SvgChart
    {
        return new SvgChart();
    }

    function setScaleDivision(): void
    {
//        print_r($this->series);
//        die;
        $this->chartMaxValue = (int)$this->series[0]['values'][0] + 1000;
        $this->chartMinValue = $this->chartMaxValue;
        foreach($this->series as $series)
        {
            foreach ($series['values'] as $value) {
                $value = $value['val'] + 1000;

                if($value > $this->chartMaxValue) {
                    $this->chartMaxValue = $value;
                }
                if ($value < $this->chartMinValue) {
                    $this->chartMinValue = $value;
                }
            }
        }
        $this->chartValuesDelta = $this->chartMaxValue - $this->chartMinValue;

        $this->chartScaleDivision = $this->chartHeight / ( $this->chartValuesDelta == 0 ? 1 : $this->chartValuesDelta );
        $this->chartMaxValue -= 1000;
        $this->chartMinValue -= 1000;
    }

    private function addGrid($html): string
    {

        // vertical
        for($i = 0; $i < count($this->series[0]['values']); $i++)
        {
            $html .= '<line x1="'. $i * $this->chartStepWidth
                . '" x2="'.$i * $this->chartStepWidth
                . '" y1="0" y2="'.$this->chartHeight.'" style="stroke:rgba(0, 0, 0, 0.2);stroke-width:1px;stroke-dasharray:2px;"></line>';

        }

        // horizontal
        $amount = 10;
        $amountScale = $this->chartHeight / $amount;
        for($i = 0; $i <= $amount; $i++)
        {
            $html .= '<line x1="0" x2="'.$this->chartStepWidth * count($this->series[0]['values'])
                . '" y1="'.$amountScale * $i.'" y2="'.$amountScale * $i.'" style="stroke:rgba(0, 0, 0, 0.2);stroke-width:1px;stroke-dasharray:2px;"></line>';
        }

        return $html;
    }

    private function getPathD($series)
    {

        $points = array();
        foreach($series['values'] as $key=>$value)
        {
            $points[] = array(
                $key * $this->chartStepWidth,
                $this->chartHeight - (
                    ($value['val'] - $this->chartMinValue) * $this->chartScaleDivision
                )
            );
        }

        $d = '';

        if($series['parameters']['bezierCurve'])
        {
            foreach ($points as $key=>$point)
            {
                if($key == 0)
                {
                    $d = 'M'.$point[0].' '.$point[1];
                }
                elseif($key == 1)
                {

                    $d .= ' C '.$points[0][0].' '.$points[0][1].', '.( $points[1][0] - ($this->chartStepWidth / 2) ).' '.$points[1][1].', '.$points[1][0].' '.$points[1][1];
                }
                else {
                    $d .= ' S '.( $point[0] - ($this->chartStepWidth / 2) ).' '.$point[1].', '.$point[0].' '.$point[1];
                }
            }
        }
        else
        {
            foreach ($points as $key=>$point)
            {
                if($key == 0)
                {
                    $d = 'M'.$point[0].' '.$point[1];
                }
                else {
                    $d .= ' L '.$point[0].' '.$point[1];
                }
            }
        }
        return $d;
    }
    private function addCharts($html): string
    {

        foreach($this->series as $series)
        {

            $d = $this->getPathD($series);
            $html .= '<path d="'.$d.'" style="fill:none;stroke:red;stroke-width:3" />';
        }
        return $html;
    }
    public function getHtml(): string
    {

        $this->setScaleDivision();
        $html = '<svg class="'.$this->cssClass.'" viewBox="0 0 ';
        $html .= ($this->canvasLeftPadding + (
                    $this->chartStepWidth * count($this->series[0]['values'])
                )) . (' ' . ($this->chartHeight + $this->canvasBottomPadding)) .'">';

        $html = $this->addGrid($html);

        $html = $this->addCharts($html);

        $html .= '</svg>';
        return $html;
    }
}
