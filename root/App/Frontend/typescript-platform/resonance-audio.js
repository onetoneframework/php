import * as resonanceAudioModule from "https://esm.sh/resonance-audio@1.0.0";

// Normalize the module shapes returned by ESM and CommonJS-compatible CDNs.
const moduleDefault = resonanceAudioModule.default;
const resolvedResonanceAudio =
	resonanceAudioModule.ResonanceAudio
	?? moduleDefault?.ResonanceAudio
	?? moduleDefault;

if (typeof resolvedResonanceAudio !== "function") {
	throw new TypeError("The ResonanceAudio constructor is unavailable.");
}

export const ResonanceAudio = resolvedResonanceAudio;
export default resolvedResonanceAudio;
