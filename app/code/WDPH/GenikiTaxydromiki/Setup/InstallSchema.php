<?php
namespace WDPH\GenikiTaxydromiki\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
	public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
	{
		$installer = $setup;
		$installer->startSetup();

        if (!$installer->tableExists('wdph_genikitaxydromiki_voucher'))
        {
            $table = $installer->getConnection()->newTable($installer->getTable('wdph_genikitaxydromiki_voucher'))
                ->addColumn(
                    'entity_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary'  => true,
                        'unsigned' => true,
                    ],
                    'Post ID'
                )
                ->addColumn(
					'pod',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable => false'],
					'POD No'
				)
                ->addColumn(
					'jobid',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable => false'],
					'Job ID'
				)
                ->addColumn(
					'orderno',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable => false'],
					'Order No'
				)
                ->addColumn(
                    'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'POD Creation Time'
                )
                ->addColumn(
					'status',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable => false'],
					'POD Status'
				)
                ->addColumn(
					'is_printed',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					['nullable => false'],
					'Print Status'
				)
                ->setComment('Voucher Table');

            $installer->getConnection()->createTable($table);

            $installer->getConnection()->addIndex(
				$installer->getTable('wdph_genikitaxydromiki_voucher'),
				$setup->getIdxName(
					$installer->getTable('wdph_genikitaxydromiki_voucher'),
					['pod','jobid','orderno','status'],
					\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
				),
				['pod','jobid','orderno','status'],
				\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
			);
        }
		$installer->endSetup();
	}
}
?>