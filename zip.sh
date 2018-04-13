filename="shoprunback-prestashop-$(date '+%Y%m%d').zip"
rm -f *.zip
cd ..
mkdir shoprunback
cp -r ./prestashop-module/* ./shoprunback
cd shoprunback
rm -r .gitignore .git README.md zip.sh
cd ..
zip -r $filename shoprunback
rm -rf shoprunback/
mv $filename prestashop-module
cd prestashop-module