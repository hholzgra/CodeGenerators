all: html pdf

html: manual manual.html

pdf: manual.pdf

manual: manual.xml
	mkdir -p manual
	docbook2html --output manual manual.xml && touch manual

manual.html: manual.xml
	docbook2html --nochunks manual.xml

manual.pdf: manual.xml
	docbook2pdf manual.xml

test:
	xmllint --valid --noout manual.xml && echo "validation OK"
