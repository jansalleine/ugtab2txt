SHELL=/bin/bash
RM=rm -f

EXECUTABLE=../build/ugtab2txt

all: ugtab2txt.php
	echo "#!/usr/bin/env php" > $(EXECUTABLE)
	php -w $< >> $(EXECUTABLE)
	chmod +x $(EXECUTABLE)

clean:
	$(RM) $(EXECUTABLE)
