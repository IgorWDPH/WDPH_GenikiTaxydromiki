<?php
namespace WDPH\GenikiTaxydromiki\Model;

use WDPH\GenikiTaxydromiki\Helper\Data;
use Magento\Framework\Stdlib\DateTime\DateTime;

class TractionInfo
{
    protected $helper;	
	protected $dateTime;
	protected $soapClient;
	protected $authKey;

    public function __construct(        
		Data $helper,
		DateTime $dateTime		
    )
	{
		$this->helper = $helper;
		$this->dateTime = $dateTime;
		$this->soapClient = $this->helper->getSoapClient();	
	}

    public function getTractionData($pod)
	{		
		$result = array();
		if(!$pod)
		{
			return $result;
		}		
        $result['pod'] = $pod;
		if(!$this->authKey)
        {
            $this->authKey = $this->helper->authenticate($this->soapClient);
        }		
		$params = array(
            'authKey' 	=> $this->authKey,
            'voucherNo' => $pod,
			'language' => 'el'
        );
		try
        {
            $response = $this->soapClient->TrackAndTrace($params);			
        }
        catch (\Exception $e)
        {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }		
		if(!$response)
		{
			return $result;
		}		
		if($response->TrackAndTraceResult->Result == 9)
		{			
			$result['error_message'] = __('Specified voucher number is not found');			
		}
		elseif($response->TrackAndTraceResult->Result == 10)
		{			
			$result['error_message'] = __('The requesting user has no right to access the specified voucher');			
		}
		elseif($response->TrackAndTraceResult->Result != 0)
		{
			$result['error_message'] = __('SOAP ERROR: TrackAndTrace() returned result code: %1', $response->TrackAndTraceResult->Result);			
		}
		else
		{
			if(is_array($response->TrackAndTraceResult->Checkpoints->Checkpoint))
			{
				$checkpoints = $response->TrackAndTraceResult->Checkpoints->Checkpoint;
				foreach($checkpoints as $checkpoint)
				{
					$result['data'][] = array('date' => $timeStamp = $this->dateTime->gmtDate('d/m/Y H:i', $checkpoint->StatusDate), 'status' => $checkpoint->Status);
				}
			}
			else
			{
				$result['data'][] = array('date' => $timeStamp = $this->dateTime->gmtDate('d/m/Y H:i', $response->TrackAndTraceResult->Checkpoints->Checkpoint->StatusDate), 'status' => $response->TrackAndTraceResult->Checkpoints->Checkpoint->Status);
			}
		}
		return $result;
	}
}
?>