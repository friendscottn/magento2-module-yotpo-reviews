<?php

namespace Yotpo\Yotpo\Model\Observer;

use Magento\Framework\Event\Observer;
use Magento\Core\Model\ObjectManager;

class PurchaseObserver 
{   

	public function __construct(
        \Yotpo\Yotpo\Helper\ApiClient $helper,
        \Yotpo\Yotpo\Block\Config $config,
        \Psr\Log\LoggerInterface $logger)
                        
	{
	    $this->_helper = $helper;
        $this->_config = $config; 
        $this->_logger = $logger;           
	}
    //observer function hooked on event sales_order_save_after
    public function dispatch(Observer $observer)
    {
        try {
            if (!$this->_config->isAppKeyAndSecretSet())
            {
                return $this;
            }            
            $order = $observer->getEvent()->getOrder();
            if($order->getStatus() != \Magento\Sales\Model\Order::STATE_COMPLETE)
            {
                return $this;
            }
            $data['email'] = $order->getCustomerEmail();
            $data['customer_name'] = $order->getCustomerName();
            $data['order_id'] = $order->getIncrementId();
            $data['platform'] = 'magento';
            $data['currency_iso'] = $order->getOrderCurrency()->getCode();
            $data['order_date'] = $order->getCreatedAt();        
            $data['products'] = $this->_helper->prepareProductsData($order); 
            $data['utoken'] = $this->_helper->oauthAuthentication();
            if ($data['utoken'] == null) {
                //failed to get access token to api
                $this->_logger->addDebug('access token recieved from yotpo api is null');  
                return $this;
            }
            $this->_helper->createPurchases($data); 
            return $this;   
        } catch(Exception $e) {
            $this->_logger->addDebug('Failed to send mail after purchase. Error: '.$e); 
        }

    }
}
