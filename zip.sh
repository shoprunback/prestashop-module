filename="shoprunback-prestashop-$(date '+%Y%m%d').zip"
rm -f filename
cd ..
mkdir shoprunback
cp -r ./prestashop-module/* ./shoprunback
cd shoprunback
rm -r .gitignore .git README.md *.zip zip.sh
cd ..
zip -r $filename shoprunback
rm -rf shoprunback/
mv $filename prestashop-module
cd prestashop-module