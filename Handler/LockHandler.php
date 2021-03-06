<?php

namespace DachcomDigital\Payum\Saferpay\Handler;

class LockHandler
{
    /**
     * @var string
     */
    protected $path;

    /**
     * LockHandler constructor.
     *
     * @param $path string
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * @param $id
     */
    public function lockTransaction($id)
    {
        // set lock
        if ($this->transactionIsLocked($id)) {
            return;
        }

        $fp = fopen($this->getTransactionLockPath($id), 'a+');
        fclose($fp);
    }

    /**
     * @param $id
     */
    public function unlockTransaction($id)
    {
        if ($this->transactionIsLocked($id)) {
            unlink($this->getTransactionLockPath($id));
        }
    }

    /**
     * @param $id
     * @return bool
     */
    public function transactionIsLocked($id)
    {
        return file_exists($this->getTransactionLockPath($id));
    }

    /**
     * @param $id
     * @return string
     */
    private function getTransactionLockPath($id)
    {
        return rtrim($this->path, '/') . '/payment-lock-' . $id . '.lock';
    }
}