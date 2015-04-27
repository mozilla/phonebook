from django.contrib.staticfiles.templatetags.staticfiles import static
from jingo import register

from pipeline.templatetags.ext import PipelineExtension


static = register.function(static)
register.function(PipelineExtension)
