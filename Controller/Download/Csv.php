<?php
namespace MN\DownloadCart\Controller\Download;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\File\Csv as FileCsv;
use Magento\Framework\App\Config\ScopeConfigInterface;


class Csv extends Action
{
    /**
     * @var FileFactory
     */
    private $fileFactory;
    /**
     * @var Csv|FileCsv
     */
    private $csvProcessor;
    /**
     * @var DirectoryList
     */
    private $directoryList;
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Csv constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param Session $checkoutSession
     * @param FileFactory $fileFactory
     * @param \MN\DownloadCart\Controller\Download\Csv $csvProcessor
     * @param DirectoryList $directoryList
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Session $checkoutSession,
        FileFactory $fileFactory,
        FileCsv $csvProcessor,
        DirectoryList $directoryList,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->fileFactory = $fileFactory;
        $this->csvProcessor = $csvProcessor;
        $this->directoryList = $directoryList;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws NotFoundException
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $isEnabled = $this->scopeConfig->getValue(
            'checkout/cart/enable_download_csv',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$isEnabled) {
            throw new NotFoundException(__('Page not found.'));
        }

        $fileName = 'cart.csv';
        $list = [['Product', 'SKU', 'QTY', 'Price']];
        $filePath = $this->directoryList->getPath(DirectoryList::VAR_DIR). "/" . $fileName;

        foreach($this->checkoutSession->getQuote()->getAllVisibleItems() as $item) {
            $list[] = [$item->getProduct()->getName(), $item->getSku(), $item->getQty(), $item->getRowTotalInclTax()];
        }

        $this->csvProcessor
            ->setDelimiter(',')
            ->setEnclosure('"')
            ->saveData(
                $filePath,
                $list
            );

        return $this->fileFactory->create(
            $fileName,
            [
                'type' => "filename",
                'value' => $fileName,
                'rm' => true,
            ],
            DirectoryList::VAR_DIR,
            'application/octet-stream'
        );
    }
}

