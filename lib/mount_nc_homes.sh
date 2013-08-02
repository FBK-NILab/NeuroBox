#!/bin/bash

for KEY in /home/neurocloud/keys/*; do
  U=$(basename $KEY)
  if ! mount -l -t fuse.sshfs | grep $U &>/dev/null; then
    echo -n "Mounting /home/neurocloud/data/$U ..."
    # make sure that the folder nc_data exists
    ssh -i $KEY -o BatchMode=yes -o StrictHostKeyChecking=no $U@korein mkdir -p nc_data  </dev/null
    sshfs $U@korempba:nc_data /home/neurocloud/data/$U -o IdentityFile=$KEY,BatchMode=yes,StrictHostKeyChecking=no,uid=33,gid=33,allow_other,umask=007 2>>/home/neurocloud/mount_err.log
    if [ $? -eq 0 ]; then
      echo "OK"
    else 
      echo "FAIL"
    fi
  fi
done

