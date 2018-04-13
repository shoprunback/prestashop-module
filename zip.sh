filename="shoprunback-prestashop-$(date '+%Y%m%d').zip"
rm -f *.zip
cd ..
mkdir shoprunback
cp -r ./prestashop-module/* ./shoprunback
cd shoprunback
composer install
rm -r .gitignore .git README.md zip.sh lib/composer
cd ..
zip -r $filename shoprunback
rm -rf shoprunback/
mv $filename prestashop-module
cd prestashop-module