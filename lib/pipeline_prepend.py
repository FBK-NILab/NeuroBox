#!/usr/bin/python
# This file will be prepended to all python scripts ran through the web interface

import nipype.pipeline.engine as pe
import nipype.interfaces.fsl

nipype.interfaces.fsl.FSLCommand.set_default_output_type('NIFTI_GZ')

# The new function that will replace the default Workflow.run
# the function will overwrite the plugin used to run the pipeline (SGE)
# and will set the plugin arguments  (the arguments to qsub)
def _nc_workflow_run(self, plugin=None, plugin_args=None, updatehash=False):
    from os import environ as env
    plugin = 'SGE'
    
    plugin_args = dict(qsub_args = '-l mf=1.4G -cwd', template = 'qsub_template.sh')

    # call to the real run function (a function created dynamically inside the Workflow class)
    print "Calling Workflow.run() with plugin = %s ; plugin_args = %s" % (plugin, plugin_args)
    self._nc_orig_run(plugin, plugin_args, updatehash)

def _nc_write_graph_noop(self, dotfilename='graph.dot', graph2use='hierarchical', format='png', simple_form=True):
    pass


# dynamically create a new function, pointing to the actual Workflow.run
pe.Workflow._nc_orig_run = pe.Workflow.run

# substitute Workflow.run with the custom function created above (which will call pe.Workflow._nc_orig_run)
pe.Workflow.run = _nc_workflow_run
# substitute write_graph with a function that does a NO-OP, because 'dot' is not installed in the cluster nodes
pe.Workflow.write_graph = _nc_write_graph_noop

# below, the user-created python script
