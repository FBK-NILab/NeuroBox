#$ -V
#$ -S /bin/sh

export FSLOUTPUTTYPE=NIFTI_GZ
export FSLDIR=/nilab0/local/fsl
export FSLTCLSH=tclsh
export FSLWISH=wish
export PATH=/nilab0/local/bin:/nilab0/local/fsl/bin:/nilab0/local/freesurfer/bin:$PATH
export FREESURFER_HOME=/nilab0/local/freesurfer
