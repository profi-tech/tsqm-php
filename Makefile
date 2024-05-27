
.PHONY ssh-keys:
ssh-keys:
	
	@if [ ! -d ~/.ssh/tsqm-php ]; then \
		mkdir -p ~/.ssh/tsqm-php && chmod 700 ~/.ssh && chmod 700 ~/.ssh/tsqm-php; \
	fi
	
	@if [ ! -f ~/.ssh/tsqm-php/id_ed25519 ]; then \
		ssh-keygen -t ed25519 -f ~/.ssh/tsqm-php/id_ed25519; \
	fi

	@echo "Copy the key below and add it to your GitHub account at https://github.com/settings/keys"
	@echo 
	@echo "----- BEGIN SSH PUBLIC KEY -----"
	@cat ~/.ssh/tsqm-php/id_ed25519.pub
	@echo "----- END SSH PUBLIC KEY -----"
	@echo 