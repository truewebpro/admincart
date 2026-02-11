
import "vuetify/dist/vuetify.min.css";
import { createVuetify } from "vuetify";
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import * as colors from "vuetify/util/colors";

export default createVuetify({
    components,
    directives,
    theme:{
        defaultTheme:'light',
        themes:{
            light:{
                colors:{
                    primary:colors.blue.darken3,
                    secondary:colors.blue.darken4,
                    success:colors.green.darken3,
                    navy:'#1B2A41',
                    teal:'#00bfa6',
                    mint:'#f1fffc',
                }
            }
        }
    },
})
