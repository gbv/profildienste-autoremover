#! /bin/bash

FILE="ProfildienstAutoRemover.phar"

if [ -f $FILE ];
then
   scp $FILE krausz@esx-118.gbv.de:/home/krausz/Profildienst/autoremover
else
   echo "File $FILE does not exists"
fi

