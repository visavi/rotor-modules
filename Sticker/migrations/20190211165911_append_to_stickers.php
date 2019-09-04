<?php

use Phinx\Migration\AbstractMigration;

class AppendToStickers extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        $categories = array_map('basename', glob(MODULES . '/Sticker/images/*', GLOB_ONLYDIR));

        foreach ($categories as $categoryName) {
            $this->execute("INSERT INTO stickers_categories (name, created_at) VALUES ('" . $categoryName . "', " . SITETIME .");");
            $lastId = $this->getAdapter()->getConnection()->lastInsertId();

            $stickers = array_map('basename', glob(MODULES . '/Sticker/images/' . $categoryName . '/*.{gif,png,jpg,jpeg}', GLOB_BRACE));

            foreach ($stickers as $stickerName) {
                $this->execute("INSERT INTO stickers (category_id, name, code) VALUES (" . $lastId . ", '/assets/modules/stickers/" . $categoryName . "/" . $stickerName . "', ':" . getBodyName($stickerName) . "');");
            }
        }

        clearCache(['stickers']);
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        $categories = array_map('basename', glob(MODULES . '/Sticker/images/*', GLOB_ONLYDIR));

        foreach ($categories as $categoryName) {

            $category = $this->fetchRow('SELECT id FROM stickers_categories WHERE name = "' . $categoryName . '" LIMIT 1;');

            if ($category) {
                $this->execute("DELETE FROM stickers_categories WHERE id='" . $category['id'] . "';");
                $this->execute("DELETE FROM stickers WHERE category_id='" . $category['id'] . "';");
            }
        }

        clearCache(['stickers']);
    }
}
