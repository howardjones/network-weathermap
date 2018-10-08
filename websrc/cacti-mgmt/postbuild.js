// Taken almost verbatim from https://gist.github.com/bfocht/a0def4432d0f93dc28bc7cf64ebe8be1

// copy the just-built js code into the place weathermap/cacti expect to find it

const fs = require('fs');
var path = require('path');

const src = './build/';
const dest = '../../cacti-resources/mgmt/';

const targets = ["main.js", "main.css", "main.css.map", "main.js.map"];


//const target = "main.js";

for (let target of targets) {

    let file = require('./build/asset-manifest.json')[target];
    console.log(`${src}${file} => ${dest}${target}`);

    if (!fs.existsSync(dest)) {
        fs.mkdirSync(dest);
    }

    if (fs.existsSync(dest + target)) {
        fs.unlinkSync(dest + target);
    }

    const oldMapFile = path.basename(file) + ".map";
    const newMapFile = path.basename(target) + ".map";

    // remove source maps from production and move file to dist folder
    fs.readFile(src + file, 'utf8', (err, data) => {
        if (err) {
            console.log('Unable to read file from manifest.');
            process.exit(1);
        }

        data = data.replace(oldMapFile, newMapFile);

        fs.writeFileSync(dest + target, data);
    });
}
